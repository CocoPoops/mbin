<?php

namespace App\Command\Update\Async;

use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Post;
use App\Entity\PostComment;
use App\Service\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class NoteVisibilityHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $client,
        private readonly SettingsManager $settingsManager
    ) {
    }

    public function __invoke(NoteVisibilityMessage $message)
    {
        $repo = $this->entityManager->getRepository($message->class);

        /**
         * @var $entity Post|PostComment
         */
        $entity = $repo->find($message->id);
        $req = $this->client->request('GET', $entity->apId, [
            'headers' => [
                'Accept' => 'application/activity+json,application/ld+json,application/json',
                'User-Agent' => 'kbinBot/0.1 (+https://'.$this->settingsManager->get('KBIN_DOMAIN').'/bot)',
            ],
        ]);

        if (Response::HTTP_NOT_FOUND === $req->getStatusCode()) {
            $entity->visibility = VisibilityInterface::VISIBILITY_PRIVATE;
            $this->entityManager->flush();
        }
    }
}
