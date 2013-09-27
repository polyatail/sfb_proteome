<html>

<head>
<title>BLAST Results</title>
</head>

<body>

<big><big>BLAST Results: blastn SFB vs MetaHIT</big></big>
<br />
<i>"Ugly but functional" -- Last Updated 1/24/2011</i>
<br />
<hr>

<?

error_reporting (E_ALL ^ E_NOTICE);

$my_host = "127.0.0.1:28375";
$my_user = "root";
$my_pass = "aids";
$my_db = "sfb";

$my_table = "20101215_blastn_sfb_metahit";

$file_sfb_nt = "./SFB.454AllContig.20101002.protein.formatted.singleline.fa";
$file_sfb_aa = "./SFB.454AllContig.20101002.protein.formatted.singleline.faa";

$file_metahit_nt = "/comp_sync/data/foreign/metahit/20101117_unique_gene_set/UniGene.cds";
$file_metahit_aa = "/comp_sync/data/foreign/metahit/20101117_unique_gene_set/UniGene.pep";

$db_conn = mysql_connect ($my_host, $my_user, $my_pass);
mysql_select_db ("sfb", $db_conn);

if ($_GET["action"] == "queryfetch") {
  $orf_nt = shell_exec ("grep -A1 -m1 " . $_GET["query"] . " " . $file_sfb_nt . " | ./fasta_formatter -w 60");
  $orf_aa = shell_exec ("grep -A1 -m1 " . $_GET["query"] . " " . $file_sfb_aa . " | ./fasta_formatter -w 60");

  echo "<form name=blastx_metahit method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_metahit.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=bx>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<form name=blastn_metahit method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_metahit.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=nuc>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<form name=blastx_nr method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_nr.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=bx>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<a href=\"?\">Main Page</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastx_metahit'].submit()\">BLASTX against MetaHIT</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastn_metahit'].submit()\">BLASTN against MetaHIT</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastx_nr'].submit()\">BLASTX against NR</a><br /><br />";

  echo "<big>Sequences</big><br>";
  echo "<pre>" . $orf_nt . "</pre>";
  echo "<pre>" . $orf_aa . "</pre>";

  exit;
}

if ($_GET["action"] == "subjectfetch") {
  $results4 = mysql_query ("SELECT * FROM metahit_orfs WHERE id = '" . addslashes (str_replace (" [translate_table: standard]", "", $_GET["query"])) . "'");
  $data4 = mysql_fetch_array ($results4);

  $orf_aa = shell_exec ("echo -e '>" . $data4["id"] . " [translate_table: standard]\\n" . $data4["aa"] . "' | ./fasta_formatter -w 60");
  $orf_nt = shell_exec ("echo -e '>" . $data4["id"] . "\\n" . $data4["nt"] . "' | ./fasta_formatter -w 60");

  echo "<form name=blastx_sfb method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_sfb.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=bx>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<form name=blastx_nr method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_nr.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=bx>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<form name=blastn_nt method=\"POST\" action=\"http://nixt.org/sfb/sfb_web/blast_nt.php\">";
  echo "<input type=hidden name=act value=BLAST>";
  echo "<input type=hidden name=seq_type value=nuc>";
  echo "<input type=hidden name=evalue value=10>";
  echo "<input type=hidden name=wsize value=0>";
  echo "<input type=hidden name=fasta value=\"" .implode("", array_slice (explode ("\n", $orf_nt), 1)) . "\">";
  echo "</form>";

  echo "<a href=\"?\">Main Page</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastx_sfb'].submit()\">BLASTX against SFB</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastx_nr'].submit()\">BLASTX against NR</a> | ";
  echo "<a href=\"#\" onClick=\"javascript: forms['blastn_nt'].submit()\">BLASTN against NT</a><br /><br />";

  echo "<big>Sequences</big><br>";
  echo "<pre>" . $orf_nt . "</pre>";
  echo "<pre>" . $orf_aa . "</pre>";

  exit;
}

# get all query_ids
$results = mysql_query ("SELECT query_id FROM $my_table GROUP BY query_id");

?>
<table cellpadding="3" cellspacing="0" border="3">
  <tr>
    <td width="20%">Query Name</td>
    <td width="55%">Subject Name (Top 5 Hits)</td>
    <td width="5%">% Query Coverage</td>
    <td width="5%">% Subject Coverage</td>
    <td width="5%">% Identity</td>
    <td width="5%">% Positive</td>
    <td width="5%">Bitscore</td>
    <td width="5%">E-value</td>
  </tr>

<?

$queries_printed = 0;

while ($data = mysql_fetch_array ($results)) {
  if ($queries_printed % 2 == 0) {
    $bgcolor = "#81BEF7";
  } else {
    $bgcolor = "#CEE3F6";
  }

  $split_query = explode ("_", $data["query_id"]);

  if ($split_query[0] == "contig") {
    array_shift ($split_query);

    $split_query[0] = "contig_" . $split_query[0];
  }

  $query_strand = "";

  switch ($split_query[3]) {
    case "minus":
      $query_strand = "-";
      break;
    case "plus":
      $query_strand = "+";
      break;
  }

  $results2 = mysql_query ("SELECT * FROM $my_table WHERE query_id = '" . $data["query_id"] . "' ORDER BY evalue ASC, bitscore DESC LIMIT 5");
  $results3 = mysql_query ("SELECT * FROM combined_annotation WHERE contig = '" . $split_query[0] . "' AND (start = '" . $split_query[1] . "' OR alt_start = '" . $split_query[1] . "') AND (stop = '" . $split_query[2] . "' OR alt_stop = '" . $split_query[2] . "') AND strand = '" . $query_strand . "'");

  $data3 = mysql_fetch_array ($results3);

  $rows_printed = 0;

  while ($data2 = mysql_fetch_array ($results2)) {
    $results4 = mysql_query ("SELECT * FROM metahit_orfs WHERE id = '" . addslashes (str_replace (" [translate_table: standard]", "", $data2["subject_id"])) . "'");
    $data4 = mysql_fetch_array ($results4);

    if ($rows_printed == 0) {
      print "  <tr bgcolor=\"$bgcolor\">\n";
      print "    <td rowspan=\"" . mysql_num_rows ($results2) . "\">\n";
      print "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=queryfetch&query=" . urlencode ($data["query_id"]) . "\">" . $data["query_id"] . "</a><br /><small>\n";
      print "<b>Baylor:</b> " . $data3["baylor_desc"] . "<br />\n";
      print "<b>RAST:</b> " . $data3["rast_desc"] . "<br /></small>\n";
      print "    </td>\n";
    } else {
      print "  <tr bgcolor=\"$bgcolor\">\n";
    }

    print "    <td><a href=\"" . $_SERVER["PHP_SELF"] . "?action=subjectfetch&query=" . urlencode ($data2["subject_id"]) . "\">" . $data2["subject_id"] . "</a></td>\n";
    print "    <td>" . round ($data2["length"] / ($data2["query_length"]), 2) . "</td>\n";
    print "    <td>" . round ($data2["length"] / strlen ($data4["nt"]), 2) . "</td>\n";
    print "    <td>" . round ($data2["identities"] / $data2["length"], 2) . "</td>\n";
    print "    <td>" . round ($data2["positives"] / $data2["length"], 2) . "</td>\n";
    print "    <td>" . $data2["bitscore"] . "</td>\n";
    print "    <td>" . $data2["evalue"] . "</td>\n";
    print "  </tr>\n";

    $rows_printed++;
  }

  $queries_printed++;
}

?>
