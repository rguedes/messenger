<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\UploadFailedException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use Throwable;

class StoreDocumentMessage extends NewMessageAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * StoreDocumentMessage constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger,
                                FileService $fileService)
    {
        parent::__construct(
            $broadcaster,
            $database,
            $dispatcher
        );

        $this->messenger = $messenger;
        $this->fileService = $fileService;
    }

    /**
     * Store / upload new document message, update thread
     * updated_at, mark read for participant, broadcast.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var UploadedFile[1]
     * @var string|null[2]
     * @return $this
     * @throws Throwable|FeatureDisabledException|UploadFailedException
     */
    public function execute(...$parameters): self
    {
        $this->isDocumentUploadEnabled();

        $this->setThread($parameters[0]);

        $file = $this->upload($parameters[1]);

        $this->handleTransactions(
            $this->messenger->getProvider(),
            'DOCUMENT_MESSAGE',
            $file,
            $parameters[2] ?? null
        )
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isDocumentUploadEnabled(): void
    {
        if (! $this->messenger->isMessageDocumentUploadEnabled()) {
            throw new FeatureDisabledException('Document messages are currently disabled.');
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws UploadFailedException
     */
    private function upload(UploadedFile $file): ?string
    {
        return $this->fileService
            ->setType('document')
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getDirectory())
            ->upload($file)
            ->getName();
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return "{$this->getThread()->getStorageDirectory()}/documents";
    }
}
