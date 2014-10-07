<?php

class LabelModel extends Model {
    protected $table = LABELS;

    public function findByLabel($label)
    {
        $where = sprintf("`label` = '%s'", mysql_real_escape_string($label));
        return $this->loadUnique($where);
    }
}