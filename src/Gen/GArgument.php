<?php

namespace Lechimp\STG\Gen;

class GArgument extends GBase
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $default;

    public function __construct($type, $name, $default = null)
    {
        assert(is_string($type) || $type === null);
        assert(is_string($name));
        assert(is_string($default) || $default === null);
        $this->type = $type;
        $this->name = $name;
        $this->default = $default;
    }

    /**
     * @inheritdoc
     */
    public function render($indentation)
    {
        assert(is_int($indentation));
        if ($this->type !== null) {
            $type = $this->type . " ";
        } else {
            $type = "";
        }
        if ($this->default !== null) {
            $default = " = " . $this->default;
        } else {
            $default = "";
        }
        return $type . '$' . $this->name . $default;
    }
}
