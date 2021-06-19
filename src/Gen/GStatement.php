<?php

namespace Lechimp\STG\Gen;

class GStatement extends GBase
{
    /**
     * @var string|Closure
     */
    protected $statement;

    public function __construct($statement)
    {
        assert(is_string($statement) || $statement instanceof \Closure);
        $this->statement = $statement;
    }

    /**
     * @inheritdoc
     */
    public function render($indentation)
    {
        assert(is_int($indentation));
        if (is_string($this->statement)) {
            return $this->render_string($indentation);
        } else {
            return $this->render_closure($indentation);
        }
    }

    protected function render_string($indentation)
    {
        return $this->indent($indentation, $this->statement) . ";";
    }

    protected function render_closure($indentation)
    {
        $ind = $this->indent($indentation, "");
        $statement = $this->statement;
        return $statement($ind);
    }
}
