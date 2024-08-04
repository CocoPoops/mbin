<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class FrontExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    // effectively a specialized version of UrlExtensionRuntime::optionsUrl for front routes
    // used for filtering link generation
    public function frontOptionsUrl(
        string $name,
        ?string $value,
        ?string $routeName = null,
        array $additionalParams = [],
    ): string {
        $request = $this->requestStack->getCurrentRequest();
        $attrs = $request->attributes;
        $route = $routeName ?? $attrs->get('_route');
    
        // Merge current route parameters with query parameters and additional parameters
        $params = array_merge(
            $attrs->get('_route_params', []),
            $request->query->all(),
            $additionalParams
        );
    
        // Filter out null values and set the new value for the specified parameter
        $params = array_filter($params, fn($v) => null !== $v);
        $params[$name] = $value;
    
        // Determine the correct route based on the 'front' prefix and '_magazine' exclusion
        if (str_starts_with($route, 'front') && !str_contains($route, '_magazine')) {
            $route = $this->getFrontRoute($route, $params);
        }
    
        // Logic to switch between 'front_magazine' and 'front_magazine_full'
        $defaultParams = [
            'content' => 'threads',
            'sortBy' => 'hot',
            'time' => '∞',
            'federation' => 'all'
        ];
    
        $currentParams = array_intersect_key($params, $defaultParams);
        $differences = array_diff_assoc($currentParams, $defaultParams);
    
        if (empty($differences) && $route === 'front_magazine_full') {
            $route = 'front_magazine';
        } elseif (!empty($differences) && $route === 'front_magazine') {
            $route = 'front_magazine_full';
        }

        return $this->urlGenerator->generate($route, $params);
    }

    /**
     * Upgrades shorter `front_*` routes to a front route that can fit all specified params.
     */
    private function getFrontRoute(string $currentRoute, array $params): string
    {
        $content = $params['content'] ?? null;
        $subscription = $params['subscription'] ?? null;

        if (\in_array($currentRoute, ['front_sub', 'front_content']) && $content && $subscription) {
            return 'front';
        } elseif ('front_short' === $currentRoute) {
            return match (true) {
                !empty($content) => 'front_content',
                !empty($subscription) => 'front_sub',
                default => 'front',
            };
        }

        return 'front';
    }
}
