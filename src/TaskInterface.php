<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

/**
 * interface for running tasks
 */
interface TaskInterface
{
    /**
     * task run method
     * When this returns without exception task will be marked as done
     */
    public function run();
}
