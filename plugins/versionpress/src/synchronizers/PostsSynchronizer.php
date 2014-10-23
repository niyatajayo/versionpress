<?php

class PostsSynchronizer extends SynchronizerBase {
    /** @var EntityFilter */
    private $filter;

    /** @var wpdb */
    private $database;

    function __construct(EntityStorage $storage, wpdb $database, DbSchemaInfo $dbSchema) {
        parent::__construct($storage, $database, $dbSchema, 'posts');
        $this->filter = new AbsoluteUrlFilter();
        $this->database = $database;
    }

    protected function filterEntities($entities) {
        $filteredEntities = array();

        foreach ($entities as $entity) {
            $entityClone = $entity;
            unset($entityClone['category'], $entityClone['post_tag']); // categories and tags are synchronized by TermRelationshipsSynchronizer
            $entityClone = $this->filter->restore($entityClone);
            $filteredEntities[] = $entityClone;
        }

        return parent::filterEntities($filteredEntities);
    }

    protected function doEntitySpecificActions() {
        $this->fixCommentCounts();
        $this->fixGuids();
    }

    private function fixCommentCounts() {
        $sql = "update {$this->database->prefix}posts set comment_count =
     (select count(*) from {$this->database->prefix}comments where comment_post_ID = {$this->database->prefix}posts.ID and comment_approved = 1);";
        $this->database->query($sql);
    }

    private function fixGuids() {
        $siteUrl = get_site_url();
        $sql = "update {$this->database->prefix}posts set guid = concat('{$siteUrl}/?p=', ID)";
        $this->database->query($sql);
    }
}