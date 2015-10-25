<?php 

$template_path = "template.rtf";

//$re_reportdate = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)REPORTDATE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 
//$re_prevreportdate = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)PREVREPORTDATE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 

//$re_ccurvalue = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)CCURVALUE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 
//$re_hcurvalue = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)HCURVALUE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 

//$re_cprevvalue = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)CPREVVALUE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 
//$re_hprevvalue = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)HPREVVALUE\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 

//$re_cconsump = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)CCONSUMP\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 
//$re_hconsump = "/\\{([^\\{\\s]+\\s+)+%\\}\\s*(\\{([^\\{\\s]+\\s+)+)HCONSUMP\\}\\s*\\{([^\\{\\s]+\\s+)+%\\}/i"; 


$re_reportdate = "/(%REPORTDATE%)/i"; 
$re_prevreportdate = "/(%PREVREPORTDATE%)/i"; 

$re_ccurvalue = "/(%CCURVALUE%)/i"; 
$re_hcurvalue = "/(%HCURVALUE%)/i"; 

$re_cprevvalue = "/(%CPREVVALUE%)/i"; 
$re_hprevvalue = "/(%HPREVVALUE%)/i"; 

$re_cconsump = "/(%CCONSUMP%)/i"; 
$re_hconsump = "/(%HCONSUMP%)/i"; 



$reportdate = '20.10.2015';
$prevreportdate = '20.09.2015';

$ccurvalue = '256';
$hcurvalue = '71';

$cprevvalue = '200';
$hprevvalue = '65';

$cconsump = '56';
$hconsump = '6';



printf("Hello, world!\n");


$buffer = file_get_contents($template_path);

// parse by segments

$buffer = preg_replace($re_reportdate, $reportdate, $buffer);
$buffer = preg_replace($re_prevreportdate, $prevreportdate, $buffer);

$buffer = preg_replace($re_ccurvalue, $ccurvalue, $buffer);
$buffer = preg_replace($re_hcurvalue, $hcurvalue, $buffer);

$buffer = preg_replace($re_cprevvalue, $cprevvalue, $buffer);
$buffer = preg_replace($re_hprevvalue, $hprevvalue, $buffer);

$buffer = preg_replace($re_cconsump, $cconsump, $buffer);
$buffer = preg_replace($re_hconsump, $hconsump, $buffer);


//printf("%s\n", $buffer);

file_put_contents("temp1.rtf", $buffer);



?>