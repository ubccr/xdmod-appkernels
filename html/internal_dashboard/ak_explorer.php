<?php

   require_once dirname(__FILE__).'/../../configuration/linker.php';

   use CCR\DB;
   
   $dbh = DB::factory('database'); 

   $r = $dbh->query('SELECT * FROM mod_appkernel.log_table LIMIT 10');
   
   //\xd_debug\dumpArray($r);

   // ---------------------------------------------
      
   foreach($r as $e) {
   
      print $e['message'];
      
      $g = explode('Cause', $e['message']);
      
      
      //$c = preg_match('/Error executing reporter (?P<reporter>.+)/', $e['message'], $m);
      $c = preg_match('/Error executing reporter (?P<reporter>.+) at (?P<datestamp>.+)\. Cause/', $e['message'], $m);
      print "[C = $c]\n";
      
      print '<br />';
      if ($c == 1) {
         \xd_debug\dumpArray($m);
      }
      
   }//foreach
   
   // ---------------------------------------------
   
   /*
      Arriving from a flight into Hazard last morning
      Not even able to heed a single warning
   */

?>
