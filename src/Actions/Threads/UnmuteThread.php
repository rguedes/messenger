<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Events\ParticipantUnMutedEvent;
use RTippin\Messenger\Models\Thread;

class UnmuteThread extends ThreadParticipantAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * UnmuteThread constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Unmute the thread for the current participant.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->updateParticipant(
                $this->getThread()->currentParticipant(),
                ['muted' => false]
            );

        if ($this->getParticipant()->wasChanged()) {
            $this->fireEvents();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantUnMutedEvent(
                $this->getParticipant(true)
            ));
        }

        return $this;
    }
}
