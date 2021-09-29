<?php

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a trait for common revision UI functionality.
 */
trait RevisionControllerTrait {

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  abstract protected function entityTypeManager();

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  abstract public function languageManager();

  /**
   * Builds a link to revert an entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity to build a revert revision link for.
   *
   * @return array|null
   *   A link to revert an entity revision, or NULL if the entity type does not
   *   have an a route to revert an entity revision.
   */
  abstract protected function buildRevertRevisionLink(RevisionableInterface $revision): ?array;

  /**
   * Builds a link to delete an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_revision
   *   The entity to build a delete revision link for.
   *
   * @return array|null
   *   A link render array.
   */
  abstract protected function buildDeleteRevisionLink(EntityInterface $entity_revision): ?array;

  /**
   * Get a description of the revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity revision.
   *
   * @return array
   *   A render array describing the revision.
   */
  abstract protected function getRevisionDescription(RevisionableInterface $revision): array;

  /**
   * Generates revisions of an entity relevant to the current user.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity.
   *
   * @return \Generator|\Drupal\Core\Entity\RevisionableInterface
   *   Generates revisions.
   */
  protected function loadRevisions(RevisionableInterface $entity) {
    $entityType = $entity->getEntityType();
    $translatable = $entityType->isTranslatable();
    $entityStorage = $this->entityTypeManager()->getStorage($entity->getEntityTypeId());
    assert($entityStorage instanceof RevisionableStorageInterface);

    $result = $entityStorage->getQuery()
      ->accessCheck(TRUE)
      ->allRevisions()
      ->condition($entityType->getKey('id'), $entity->id())
      ->sort($entityType->getKey('revision'), 'DESC')
      ->execute();

    $currentLangcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    foreach ($entityStorage->loadMultipleRevisions(array_keys($result)) as $revision) {
      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!$translatable || ($revision->hasTranslation($currentLangcode) && $revision->getTranslation($currentLangcode)->isRevisionTranslationAffected())) {
        yield $revision;
      }
    }
  }

  /**
   * Generates an overview table of revisions of an entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   A revisionable entity.
   *
   * @return array
   *   A render array.
   */
  protected function revisionOverview(RevisionableInterface $entity): array {
    $build['entity_revisions_table'] = [
      '#theme' => 'table',
      '#header' => [
        'revision' => ['data' => $this->t('Revision')],
        'operations' => ['data' => $this->t('Operations')],
      ],
    ];
    foreach ($this->loadRevisions($entity) as $revision) {
      $row = $this->buildRow($revision);
      if (empty($row)) {
        continue;
      }
      $build['entity_revisions_table']['#rows'][$revision->getRevisionId()] = $row;
    }

    (new CacheableMetadata())
      // Only dealing with this entity and no external dependencies.
      ->addCacheableDependency($entity)
      ->addCacheContexts(['languages:language_content'])
      ->applyTo($build);

    return $build;
  }

  /**
   * Builds a table row for a revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   An entity revision.
   *
   * @return array
   *   A table row.
   */
  protected function buildRow(RevisionableInterface $revision): array {
    $row = [];
    $rowAttributes = [];

    $row['revision']['data'] = $this->getRevisionDescription($revision);
    $row['operations']['data'] = [];

    // Revision status.
    if ($revision->isDefaultRevision()) {
      $rowAttributes['class'][] = 'revision-current';
      $row['operations']['data']['status']['#markup'] = $this->t('<em>Current revision</em>');
    }

    // Operation links.
    $links = $this->getOperationLinks($revision);
    if (count($links) > 0) {
      $row['operations']['data']['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    return ['data' => $row] + $rowAttributes;
  }

  /**
   * Get operations for an entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity to build revision links for.
   *
   * @return array
   *   An array of operation links.
   */
  protected function getOperationLinks(RevisionableInterface $revision): array {
    // Removes links which are inaccessible or not rendered.
    return array_filter([
      $this->buildRevertRevisionLink($revision),
      $this->buildDeleteRevisionLink($revision),
    ]);
  }

}
