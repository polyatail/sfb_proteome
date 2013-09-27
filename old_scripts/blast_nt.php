<?

error_reporting (E_ALL ^ E_NOTICE);

$nt_db = "/comp_sync/data/foreign/blast/db_nt/nt";
$aa_db = "";

$lang1 = "NT (Nucleotide Collection)";
$lang2 = "NT";

$_POST = array_merge ($_GET, $_POST);

?>

<div id="content"> 
<h2>BLAST against <?=$lang1?></h2>
<p style='width:800px;'>To BLAST against <?=$lang2?>, paste a sequence into the box below. Please select whether you are pasting in nucleotides or amino acids. Then press the button labeled <b>BLAST</b>.</p>

<div style='padding-left: 10px;'>
<form method='post' id='blast_form' enctype='multipart/form-data' action='<?=$_SERVER["PHP_SELF"]?>' style='margin: 0px; padding: 0px;'> 

<table>
  <tr>

    <th>sequence</th>
    <td><input type='radio' name='seq_type' value='nuc' checked=checked>nt</td>

  </tr> 

  <tr>

    <th>cutoff</th>
    <td colspan=2><input type='text' name='evalue' value='<?=$_POST["evalue"] ? $_POST["evalue"] : "10"?>' size=5>&nbsp;<i>(e.g.: 1e-30)</i></td>

    <th>word size</th>
    <td colspan=2><input type='text' name='wsize' value='<?=$_POST["wsize"] ? $_POST["wsize"] : "0"?>' size=5>&nbsp;<i>(0 for default)</i></td>

  </tr>
</table>

<textarea style='width: 550px;height: 150px;' name='fasta'><?=$_POST["fasta"]?></textarea>
<br/><br/> 
<input type='hidden' name='organism' value='49118.4'>
<input type='hidden' name='page' id='page' value='BlastRun'> 
<input type='submit' name='act' class='button' value='BLAST'>
&nbsp;&nbsp;&nbsp;
<input type='reset' class='button' value='Clear'>
&nbsp;

</form>
</div>

<?

if ($_POST["act"] == "BLAST") {
  $_POST["wsize"] = intval ($_POST["wsize"]);

  $fasta_lines = explode ("\n", $_POST["fasta"]);

  for ($i = 0; $i < count ($fasta_lines); $i++) {
    $fasta_lines[$i] = chop ($fasta_lines[$i]);
  }

  if ($fasta_lines[0][0] == ">") {
    $_POST["fasta"] = implode ("", array_slice ($fasta_lines, 1));
  } else {
    $_POST["fasta"] = implode ("", $fasta_lines);
  }

  $_POST["fasta"] = str_replace("*", "X", $_POST["fasta"]);

  if ($_POST["wsize"] != 0) {
    if ($_POST["seq_type"] == "nuc") {
      if ($_POST["wsize"] < 4) {
        echo "<br>Word size for nucleotide BLAST must be >=4!";
        exit;
      }
    } elseif ($_POST["seq_type"] == "aa" || $_POST["seq_type"] == "bx") {
      if ($_POST["wsize"] > 8 || $_POST["wsize"] < 2) {
        echo "<br>Word size for protein BLAST must be >1 and <8!";
        exit;
      } 
    }

    $word_size = " -word_size " . $_POST["wsize"];
  } else {
    $word_size = "";
  }

  if (strlen ($_POST["fasta"]) <= 30) {
    $task = "-short";
  } else {
    $task = "";
  }

  if ($_POST["seq_type"] == "nuc") {
    $blast_cmd = "blastn -num_threads 30 -db " . $nt_db . " -task blastn" . $task;
  } elseif ($_POST["seq_type"] == "aa") {
    $blast_cmd = "blastp -num_threads 30 -db " . $aa_db . " -task blastp" . $task;
  } elseif ($_POST["seq_type"] == "bx") {
    $blast_cmd = "blastx -num_threads 30 -db " . $aa_db;
  } else {
    print "Error selecting BLAST type.";
    exit;
  }

  $submit_fasta = ">User-Submitted Query\\n" . escapeshellcmd ($_POST["fasta"]);

  $results = shell_exec ("echo -e '" . $submit_fasta . "' | " . $blast_cmd . " -html -evalue " . escapeshellcmd ($_POST["evalue"]) . $word_size);

  echo $results;
}

?>
