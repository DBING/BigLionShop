<?php
define('PT',3.1415926);
$r=3;
//面积
echo PT * $r*$r;
echo'<hr>';
//周长
echo 2 * PT * $r;

class CircleSize
{
      function getS($r){
          return 3.14*$r*$r;
      }
      function getC($r)
      {
          return 2 * 3.14 * $r;
      }
}
$circle=new CircleSize;
echo $circle->getS(10);
echo '<br>';
echo $circle->getS(100);
echo '<br>';
echo $circle->getC(10);
