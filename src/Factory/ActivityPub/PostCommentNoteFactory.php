<?php

declare(strict_types=1);

namespace App\Factory\ActivityPub;

use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Entity\PostComment;
use App\Markdown\MarkdownConverter;
use App\Service\ActivityPub\ApHttpClient;
use App\Service\ActivityPub\Wrapper\ImageWrapper;
use App\Service\ActivityPub\Wrapper\MentionsWrapper;
use App\Service\ActivityPub\Wrapper\TagsWrapper;
use App\Service\ActivityPubManager;
use App\Service\MentionManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostCommentNoteFactory
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PostNoteFactory $postNoteFactory,
        private readonly ImageWrapper $imageWrapper,
        private readonly GroupFactory $groupFactory,
        private readonly TagsWrapper $tagsWrapper,
        private readonly MentionsWrapper $mentionsWrapper,
        private readonly MentionManager $mentionManager,
        private readonly ApHttpClient $client,
        private readonly ActivityPubManager $activityPubManager,
        private readonly MarkdownConverter $markdownConverter
    ) {
    }

    public function create(PostComment $comment, bool $context = false): array
    {
        if ($context) {
            $note['@context'] = [
                ActivityPubActivityInterface::CONTEXT_URL,
                ActivityPubActivityInterface::SECURITY_URL,
                PostNoteFactory::getContext(),
            ];
        }

        $tags = $comment->tags ?? [];
        if ('random' !== $comment->magazine->name && !$comment->magazine->apId) { // @todo
            $tags[] = $comment->magazine->name;
        }

        $note = array_merge($note ?? [], [
            'id' => $this->getActivityPubId($comment),
            'type' => 'Note',
            'attributedTo' => $this->activityPubManager->getActorProfileId($comment->user),
            'inReplyTo' => $this->getReplyTo($comment),
            'to' => [
                ActivityPubActivityInterface::PUBLIC_URL,
            ],
            'cc' => [
                $this->groupFactory->getActivityPubId($comment->magazine),
                $comment->apId
                    ? ($this->client->getActorObject($comment->user->apProfileId)['followers']) ?? []
                    : $this->urlGenerator->generate(
                        'ap_user_followers',
                        ['username' => $comment->user->username],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
            ],
            'sensitive' => $comment->post->isAdult(),
            'content' => $this->markdownConverter->convertToHtml(
                $this->mentionManager->joinMentionsToBody($comment->body ?? '', $comment->mentions ?? []),
            ),
            'mediaType' => 'text/html',
            'url' => $this->getActivityPubId($comment),
            'tag' => array_merge(
                $this->tagsWrapper->build($tags),
                $this->mentionsWrapper->build($comment->mentions ?? [], $comment->body)
            ),
            'published' => $comment->createdAt->format(DATE_ATOM),
        ]);

        if ($comment->image) {
            $note = $this->imageWrapper->build($note, $comment->image, $comment->getShortTitle());
        }

        $mentions = [];
        foreach ($comment->mentions ?? [] as $mention) {
            try {
                $mentions[] = $this->activityPubManager->webfinger($mention)->getProfileId();
            } catch (\Exception $e) {
                continue;
            }
        }

        $note['to'] = array_values(
            array_unique(
                array_merge(
                    $note['to'],
                    $mentions,
                    $this->activityPubManager->createCcFromBody($comment->body),
                    [$this->getReplyToAuthor($comment)],
                )
            )
        );

        return $note;
    }

    public function getActivityPubId(PostComment $comment): string
    {
        if ($comment->apId) {
            return $comment->apId;
        }

        return $this->urlGenerator->generate(
            'ap_post_comment',
            [
                'magazine_name' => $comment->magazine->name,
                'post_id' => $comment->post->getId(),
                'comment_id' => $comment->getId(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getReplyTo(PostComment $comment): string
    {
        if ($comment->apId) {
            return $comment->apId;
        }

        return $comment->parent ? $this->getActivityPubId($comment->parent) : $this->postNoteFactory->getActivityPubId(
            $comment->post
        );
    }

    private function getReplyToAuthor(PostComment $comment): string
    {
        return $comment->parent
            ? $this->activityPubManager->getActorProfileId($comment->parent->user)
            : $this->activityPubManager->getActorProfileId($comment->post->user);
    }
}
