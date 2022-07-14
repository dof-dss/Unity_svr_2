<?php

namespace Drupal\uregni_breadcrumbs;

/**
 * @file
 * Generates the breadcrumb trail for content including:
 * - Board member
 *
 * In the format:
 * > Home
 * > About us
 * > Board members
 * > current-page-title
 *
 * > <front>
 * > /about-us
 * > /our-board
 * > /current-page-title
 */
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class BoardBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Node object, or null if on a non-node page.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TitleResolverInterface $title_resolver, RequestStack $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->titleResolver = $title_resolver;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('title_resolver'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $match = FALSE;
    $route_name = $route_match->getRouteName();
    if ($route_name == 'entity.node.canonical') {
      $this->node = $route_match->getParameter('node');
      if ($this->node instanceof NodeInterface == FALSE) {
        $this->node = $this->entityTypeManager->getStorage('node')->load($this->node);
      }
      if (!empty($this->node)) {
        if ($this->node->bundle() == 'staff_member') {
          $match = TRUE;
        }
      }
    }
    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $title_resolver = $this->titleResolver->getTitle($this->request->getCurrentRequest(), $route_match->getRouteObject());
    if ($this->node) {
      $links[] = Link::createFromRoute(t('Home'), '<front>');
      $links[] = Link::fromTextandUrl(t('About us'), Url::fromUri('internal:/about-us'));
      $links[] = Link::fromTextandUrl(t('Our board'), Url::fromUri('internal:/our-board'));
      $links[] = Link::createFromRoute($title_resolver, '<none>');
      $breadcrumb->setLinks($links);
    }
    $breadcrumb->addCacheContexts(['url.path']);
    return $breadcrumb;
  }

}
