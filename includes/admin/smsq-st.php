<?php

$response = wp_remote_get( "https://api.smsq.global/api/v2/Balance?ApiKey=" . $smsq_settings['clave_smsq_esms'] . "&ClientId=".$smsq_settings['apitoken_smsq']);
$body     = wp_remote_retrieve_body( $response );

   $json_call = json_decode($body, true);;
   $blc = $json_call['Data'][0]['Credits'];
   $blc_msg = $json_call['ErrorDescription'];
   if($blc_msg =="Success"){
?>

<style>
    
    .responstable {
  margin: 1em 0;
  width: 100%;
  overflow: hidden;
  background: #FFF;
  color: #024457;
  border-radius: 10px;
  border: 1px solid #00C853;
}
.responstable tr {
  border: 1px solid #00C853;
}
.responstable tr:nth-child(odd) {
  background-color: #EAF3F3;
}
.responstable th {
  display: none;
  border: 1px solid #FFF;
  background-color: #00C853;
  color: #FFF;
  padding: 1em;
}
.responstable th:first-child {
  display: table-cell;
  text-align: center;
}
.responstable th:nth-child(2) {
  display: table-cell;
}
.responstable th:nth-child(2) span {
  display: none;
}
.responstable th:nth-child(2):after {
  content: attr(data-th);
}
@media (max-width: 480px) {
  .responstable th:nth-child(2) span {
    display: block;
  }
  .responstable th:nth-child(2):after {
    display: none;
  }
}
.responstable td {
  display: block;
  word-wrap: break-word;
  max-width: 7em;
}
.responstable td:first-child {
  display: table-cell;
  text-align: center;
  border-right: 1px solid #D9E4E6;
}
@media (min-width: 480px) {
  .responstable td {
    border: 1px solid #D9E4E6;
  }
}
.responstable th, .responstable td {
  text-align: left;
  margin: .5em 1em;
}
@media (min-width: 480px) {
  .responstable th, .responstable td {
    display: table-cell;
    padding: 1em;
  }
}
</style>
<table class="responstable">
<tr>
    <th>Status: Connected</th>
    <th>Balance: <?php echo $blc; ?></th>
  </tr>
  </table>
<?php
   }else{
       
?>

<style>
    
    .responstable {
  margin: 1em 0;
  width: auto;
  overflow: hidden;
  background: #FFF;
  color: #024457;
  border-radius: 10px;
  border: 1px solid #b01212;
}
.responstable tr {
  border: 1px solid #b01212;
}
.responstable tr:nth-child(odd) {
  background-color: #EAF3F3;
}
.responstable th {
  display: none;
  border: 1px solid #FFF;
  background-color: #b01212;
  color: #FFF;
  padding: 1em;
}
.responstable th:first-child {
  display: table-cell;
  text-align: center;
}
.responstable th:nth-child(2) {
  display: table-cell;
}
.responstable th:nth-child(2) span {
  display: none;
}
.responstable th:nth-child(2):after {
  content: attr(data-th);
}
@media (max-width: 80px) {
  .responstable th:nth-child(2) span {
    display: block;
  }
  .responstable th:nth-child(2):after {
    display: none;
  }
}
.responstable td {
  display: block;
  word-wrap: break-word;
  max-width: 7em;
}
.responstable td:first-child {
  display: table-cell;
  text-align: center;
  border-right: 1px solid #D9E4E6;
}
@media (min-width: 80px) {
  .responstable td {
    border: 1px solid #D9E4E6;
  }
}
.responstable th, .responstable td {
  text-align: left;
  margin: .5em 1em;
}
@media (min-width: 80px) {
  .responstable th, .responstable td {
    display: table-cell;
    padding: 1em;
  }
}
</style>
<table class="responstable">
<tr>
    <th>Status: Disconnected</th>
  </tr>
  </table>

<?php } ?>