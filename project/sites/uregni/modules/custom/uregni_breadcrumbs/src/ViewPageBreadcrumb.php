<?php

namespace Drupal\uregni_breadcrumbs;

/**
 * @file
 * Generates the breadcrumb trail for search page(s)
 *
 * In the format:
 * > Home
 * > current-page-title
 *
 * > <front>
 * > /current-page-title
 */
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class ViewPageBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Class constructor.
   */
  public function __construct(RequestStack $request, TitleResolverInterface $title_resolver) {
    $this->request = $request;
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $match = FALSE;
    $route_name = $route_match->getRouteName();
    $view_names = [
      'view.consultations_search.consultations_search_page',
      'view.news_search.news_search_page',
      'view.publications_search.publication_search_page',
    ];

    foreach ($view_names as $view_name) {
      if ($route_name == $view_name) {
        $match = TRUE;
      }
    }
    return $match;

  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $breadcrumb = new Breadcrumb();
    $title_resolver = $this->titleResolver->getTitle($this->request->getCurrentRequest(), $route_match->getRouteObject());
    $links[] = Link::createFromRoute(t('Home'), '<front>');

    if ($route_name == 'view.publications_search.publication_search_page') {
      $links[] = Link::fromTextandUrl(t('About us'), Url::fromUri('internal:/about-us'));
    }

    $links[] = Link::createFromRoute($title_resolver, '<none>');
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}
