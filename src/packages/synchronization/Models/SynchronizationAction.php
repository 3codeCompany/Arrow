<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 16.03.2018
 * Time: 09:20
 */

namespace Arrow\Shop\Models\Esotiq\Synchronization;


class SynchronizationAction
{
    /**
     * @var string
     */
    public $actionName;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string[]
     */
    public $methods = [];

    /**
     * @var SynchronizationAction[]
     */
    public $subTasks = [];

    public function __construct(string $label, string $actionNamne, array $methods, $subTasks = [])
    {
        $this->label = $label;
        $this->actionName = $actionNamne;
        $this->methods = $methods;
        $this->subTasks = $subTasks;
    }


}
