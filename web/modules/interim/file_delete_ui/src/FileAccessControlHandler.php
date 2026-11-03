<?php

namespace Drupal\file_delete_ui;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessControlHandler as CoreAccessControlHandler;

/**
 * Provides a File access control handler.
 */
class FileAccessControlHandler extends CoreAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      $account = $this->prepareUser($account);
      /** @var \Drupal\file\Entity\File $entity */
      return AccessResult::allowedIfHasPermission($account, 'delete any file')
        ->orIf(AccessResult::allowedIf($entity->getOwnerId() == $account->id()))->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
