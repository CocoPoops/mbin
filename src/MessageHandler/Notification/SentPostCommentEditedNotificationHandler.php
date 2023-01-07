<?php

declare(strict_types=1);

namespace App\MessageHandler\Notification;

use App\Message\Notification\PostCommentEditedNotificationMessage;
use App\Repository\PostCommentRepository;
use App\Service\NotificationManager;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SentPostCommentEditedNotificationHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly PostCommentRepository $repository,
        private readonly NotificationManager $manager
    ) {
    }

    public function __invoke(PostCommentEditedNotificationMessage $message)
    {
        $comment = $this->repository->find($message->commentId);

        if (!$comment) {
            throw new UnrecoverableMessageHandlingException('Comment not found');
        }

        $this->manager->sendEdited($comment);
    }
}
