<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Icon extends Component
{
    public $name;
    public $size;
    public $color;
    public $class;

    public function __construct($name, $size = null, $color = null, $class = null)
    {
        $this->name = $name;
        $this->size = $size;
        $this->color = $color;
        $this->class = $class;
    }

    public function render()
    {
        return view('components.icon');
    }
}
