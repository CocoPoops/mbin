<?php declare(strict_types=1);

namespace App\ApiDataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\DTO\PostCommentDto;
use App\Factory\PostCommentFactory;
use App\PageView\PostCommentPageView;
use App\Repository\PostCommentRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

final class PostCommentCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private PostCommentRepository $repository,
        private PostCommentFactory $factory,
        private RequestStack $request
    ) {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return PostCommentDto::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        try {
            $criteria = new PostCommentPageView((int) $this->request->getCurrentRequest()->get('page', 1));
            $comments = $this->repository->findByCriteria($criteria);
        } catch (Exception $e) {
            return [];
        }

        $dtos = array_map(fn($comment) => $this->factory->createDto($comment), (array) $comments->getCurrentPageResults());

        return new DtoPaginator($dtos, 0, PostCommentRepository::PER_PAGE, $comments->getNbResults());
    }
}

