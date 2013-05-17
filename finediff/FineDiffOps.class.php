<?php

class FineDiffOps 
{
  public $edits = array();
  
  public function appendOpcode($opcode, $from, $from_offset, $from_len) 
  {
    if ( $opcode === 'c' ) 
    {
    $edits[] = new FineDiffCopyOp($from_len);
    }
    else if 
    ( $opcode === 'd' ) 
    {
      $edits[] = new FineDiffDeleteOp($from_len);
    }
    else /* if ( $opcode === 'i' ) */ 
    {
      $edits[] = new FineDiffInsertOp(substr($from, $from_offset, $from_len));
    }
  }
}
?>