<?

error_reporting (E_ALL ^ E_NOTICE);

#$my_host = "127.0.0.1:28375";
$my_host = "littman60";
$my_user = "root";
$my_pass = "aids";
$my_db = "sfb";
$my_table = "20110207_psiblast_sfb_nr";

#$export_nt_cmd = "ssh blast@localhost -p 28374 python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta nt_stdout";
#$export_aa_cmd = "ssh blast@localhost -p 28374 python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta aa_stdout";
#$export_rna_cmd = "ssh blast@localhost -p 28374 python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta rna_stdout";

$export_nt_cmd = "python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta nt_stdout";
$export_aa_cmd = "python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta aa_stdout";
$export_rna_cmd = "python /comp_node0/andrew/work/sfb/sfb_analysis.py sql_to_fasta rna_stdout";

$kegg_to_ko = "/comp_sync/data/foreign/kegg/20110209_kegg_ko/20110209_ko_to_koname.txt";
$psiblast_hits = "/comp_node0/andrew/proj/sfb/20110207_psiblast_sfb_nr/20110207_psiblast_hit_counts.txt";

$blues = array ("#f7fbff", "#deebf7", "#c6dbef", "#9ecae1", "#6baed6", "#4292c6", "#2171b5", "#08519c");
$reds = array_reverse (array ("#fff5f0", "#fee0d2", "#fcbba1", "#fc9272", "#fb6a4a", "#ef3b2c", "#cb181d", "#a50f15"));

$evalue_cutoff = 0.05;

# handle export tasks that need to be run before headers are sent

function shell_exec_wrap ($cmd) {
    $return = shell_exec (escapeshellcmd ($cmd) . " 2>&1");

    if (strpos ($return, "Connection refused")) {
        return false;
    } else {
        return $return;
    }
}

function send_txt_download ($text, $filename) {
    if (headers_sent()) {
        die ("ERROR: Headers already sent!");
    }

    if (ini_get ("zlib.output_compression")) {
        ini_set ("zlib.output_compression", "Off");
    }  

    $fsize = strlen ($text); 
    $ctype = "application/force-download";

    header ("Pragma: public");
    header ("Expires: 0");
    header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header ("Cache-Control: private", false);
    header ("Content-Type: " . $ctype);
    header ("Content-Disposition: attachment; filename=\"" . $filename . "\";" );
    header ("Content-Transfer-Encoding: binary");
    header ("Content-Length: " . $fsize);

    ob_clean();
    flush();

    print $text;
} 

function rgb2html ($r, $g=-1, $b=-1) {
    if (is_array($r) && sizeof($r) == 3)
        list($r, $g, $b) = $r;

    $r = intval($r); $g = intval($g);
    $b = intval($b);

    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}

if ($_GET["task"] == "downloadnt") {
    $output = shell_exec_wrap ($export_nt_cmd);
    $outfile = "sfb.all.fa";
} elseif ($_GET["task"] == "downloadaa") {
    $output = shell_exec_wrap ($export_aa_cmd);
    $outfile = "sfb.protein.faa";
} elseif ($_GET["task"] == "downloadrna") {
    $output = shell_exec_wrap ($export_rna_cmd);
    $outfile = "sfb.RNA.fa";
}

if ($output) {
    send_txt_download ($output, date ("Ymd") . "_" . $outfile);
    exit;
} elseif ($outfile) {
    print "Tunnel to NYUMC is down!  Wait ~5 minutes for automated restart cronjob...";
    exit;
}

?>

<title>PSI-BLAST: SFB vs NR</title>

<style type="text/css">
body {
    font-family: Helvetica;
}

table {
    border: 2px solid #A0A0A0;
}

tr, td {
    border: 1px solid #A0A0A0;
}

a:link, a:visited, a:active {
    color: #003366;
}

a:hover {
    color: #2e5882;
}
</style>

<body alink='#003366' vlink='#003366' link='#003366'>

<table cellpadding='4' cellspacing='0' style='font-size: 0.8em; font-weight: bold' width='900'>
    <tr height='35'>
        <td rowspan='3' width='150'><center><big><big><big>PSI-BLAST<br>SFB vs NR</big></big></big></center></td>
        <td><a href="?task=all">All SFB ORFs</a></td>
        <td><a href="?task=nosymbol">No Gene Symbol</a></td>
        <td><a href="?task=somehypo">No KO, Hypothetical in 1-2 Annotations</a></td>
        <td><a href="?task=annonoko">No KO, Have Annotations</a></td>
    </tr>
    <tr height='35'>
        <td>KO&nbsp;&nbsp;&nbsp; <a href="?task=haveko">Have</a> | <a href="?task=donthaveko">Don't Have</a></td>
        <td><a href="?task=nomanual">No Manual Curation</a></td>
        <td><a href="?task=allhypo">No KO, Hypothetical in All Annotations</a></td>
        <td>Export All&nbsp;&nbsp;&nbsp; <a href='?task=downloadnt'>NT</a> | <a href='?task=downloadaa'>AA</a> | <a href='?task=downloadrna'>RNA</a></td>
    </tr>
    <tr height='35'>
        <td>OG ID&nbsp;&nbsp;&nbsp; <a href="?task=haveog">Have</a> | <a href='?task=donthaveog'>Don't Have</a></td>
        <td colspan='3'>Localization&nbsp;&nbsp;&nbsp; <a href='?task=local&subquery=Cytoplasmic'>Cytoplasmic</a> | <a href='?task=local&subquery=CytoplasmicMembrane'>Membrane</a> | <a href='?task=local&subquery=Cellwall'>Cell Wall</a> | <a href='?task=local&subquery=Extracellular'>Extracellular</a> | <a href='?task=local&subquery=Unknown'>Unknown</a> | <a href='?task=local&subquery=NULL'>NULL</a></td>
    </tr>
</table>

<br>

<?

$count_fp = file ($psiblast_hits);

foreach ($count_fp as $line) {
    $split_line = explode ("\t", chop ($line));

    $count_matrix[$split_line[0]] = array ($split_line[1], $split_line[2]);
}

mysql_connect ($my_host, $my_user, $my_pass);
mysql_select_db ($my_db);

function print_list ($result) {
    global $count_matrix, $my_table;

    $bgcolors = array ("#D0D0D0", "#F0F0F0");

    echo "ORFs in this bin: <b>" . mysql_num_rows ($result) . "</b><br><br>";

    echo "<table cellpadding='4' cellspacing='0' width='1200'>\n";
    echo "  <tr bgcolor='#B0B0B0' style='font-size: 0.8em'>\n";
    echo "    <td width='10%' style='border: inherit'><b>SFB ORF Name</b></td>\n";
    echo "    <td width='10%'><b>Contig Name</b></td>\n";
    echo "    <td width='5%'><b>Start</b></td>\n";
    echo "    <td width='5%'><b>Stop</b></td>\n";
    echo "    <td width='8%'><b>KO/OG ID</b></td>\n";
    echo "    <td width='3%'><b><center># H</b></center></td>\n";
    echo "    <td width='3%'><b><center>% H</center></b></td>\n";
    echo "    <td width='9%'><b>Symbol</b></td>\n";
    echo "    <td width='1%'><b><center>C</center></b></td>\n";
    echo "    <td width='47%'><b>Annotation</b></td>\n";
    echo "  </tr>\n";

    $rowcount = 0;
 
    while ($data = mysql_fetch_array ($result)) {
        $kegg_ko = $data["kegg"] ? "<a href='http://www.genome.jp/dbget-bin/www_bget?ko:" . $data["kegg"] . "' target='_blank'>" . $data["kegg"] . "</a>" : "-";

        if ($kegg_ko == "-" && $data["og"]) {
            $kegg_ko = $data["og"];
        }

        if (substr ($kegg_ko, 0, 3) == "COG") {
            $kegg_ko = "<a href='http://www.ncbi.nlm.nih.gov/COG/grace/wiew.cgi?" . $kegg_ko . "' target='_blank'>" . $kegg_ko . "</a>";
        }

        if (substr ($kegg_ko, 0, 3) == "NOG") {
            $kegg_ko = "<a href='http://eggnog.embl.de/cgi_bin/display_single_node.pl?node=" . $kegg_ko . "' target='_blank'>" . $kegg_ko . "</a>";
        }

        $a_star = "<b>@</b>";

        $anno_sym_pref = "";
        $lookat_sym_pref = false;

        if ($data["curated_symbol"]) {
            if ($data["curated_symbol"] == $data["baylor_symbol"]) {
                $anno_sym_pref = "[B]";
            } elseif ($data["curated_symbol"] == $data["kegg_symbol"]) {
                $anno_sym_pref = "[K]";
            } elseif (in_array (strtolower ($data["curated_symbol"]), explode (", ", strtolower ($data["kegg_symbol"])))) {
                $anno_sym_pref = "[K*]";
            } elseif (strpos (strtolower ($data["rast"]), strtolower ($data["curated_symbol"])) !== false) {
                $anno_sym_pref = "[R*]";
            } elseif (strpos (strtolower ($data["img_er"]), strtolower ($data["curated_symbol"])) !== false) {
                $anno_sym_pref = "[I*]";
            } else {
                $anno_sym_pref = "[M]";
                $lookat_sym_pref = true;
            }

            $a_start = "<b>*</b>";
            $anno_symbol = $data["curated_symbol"];
        } elseif ($data["curated_name"]) {
            $anno_symbol = "-";
        } elseif ($data["baylor_symbol"]) {
            $anno_sym_pref = "[B]";
            $anno_symbol = $data["baylor_symbol"];
        } elseif ($data["kegg_symbol"] && preg_match ("/^[a-z]{3}[A-Z]*$/", $data["kegg_symbol"]) > 0) {
            $anno_sym_pref = "[K]";
            $anno_symbol = $data["kegg_symbol"];
        } else {
            $anno_symbol = "-";
        }

        $anno_prefix = "";
        $lookat_anno_pref = false;

        if ($data["curated_name"]) {
            $data["kegg_name"] = preg_replace ("/\s\[EC.*\]/", "", $data["kegg_name"]);

            if (strtolower ($data["curated_name"]) == preg_replace ("/\s+" . strtolower ($anno_symbol) . "\s*/", "", strtolower ($data["kegg_name"]))) {
                $anno_prefix = "[kegg]";
            } elseif ($data["baylor"] == "conserved hypothetical protein" && $data["curated_name"] == "hypothetical protein") {
                $anno_prefix = "[baylor]";
            } elseif (strtolower ($data["curated_name"]) == preg_replace ("/\s+" . strtolower ($anno_symbol) . "\s*/", "", strtolower ($data["baylor"]))) {
                $anno_prefix = "[baylor]";
            } elseif (strtolower ($data["curated_name"]) == preg_replace ("/\s+" . strtolower ($anno_symbol) . "\s*/", "", strtolower ($data["rast"]))) {
                $anno_prefix = "[rast]";
            } elseif (strtolower ($data["curated_name"]) == preg_replace ("/\s+" . strtolower ($anno_symbol) . "\s*/", "", strtolower ($data["img_er"]))) {
                $anno_prefix = "[img_er]";
            } elseif (strpos ($data["curated_name"], "domain-containing")) {
                $anno_prefix = "[CDD]";
            } else {
                $anno_prefix = "[manual]";
                $lookat_anno_pref = true;
            }

            $a_star = "<b>*</b>";
            $annotation = $data["curated_name"];
        } elseif ($data["baylor"]) {
            $anno_prefix = "[baylor]";
            $annotation = $data["baylor"];
        } elseif ($data["rast"]) {
            $anno_prefix = "[rast]";
            $annotation = $data["rast"];
        } elseif ($data["img_er"]) {
            $anno_prefix = "[img_er]";
            $annotation = $data["img_er"];
        }

        if ($lookat_anno_pref || $lookat_sym_pref) {
            $blast_result = mysql_query ("SELECT * FROM " . $my_table . " WHERE query_id = '" . $data["locus_tag"] . "' ORDER BY bitscore DESC LIMIT 50");

            while ($blast_data = mysql_fetch_array ($blast_result)) {
                $bracketpos = strpos ($blast_data["subject_id"], " [");

                if ($bracketpos === false) { 
                    $firstpart = $blast_data["subject_id"];
                } else {
                    $firstpart = substr ($blast_data["subject_id"], 0, $bracketpos);
                    $nextpart = substr ($blast_data["subject_id"], $bracketpos);
                }

                if ($lookat_anno_pref && strtolower ($annotation) == preg_replace ("/\s+" . strtolower ($anno_symbol) . "\s*/", "", strtolower ($firstpart))) {
                    $anno_prefix = "[blast]";
                    $lookat_anno_pref = false;
                }

                if ($lookat_sym_pref && strpos (strtolower ($firstpart), strtolower ($anno_symbol)) !== false) {
                    $anno_sym_pref = "[BL]";
                    $lookat_sym_pref = false;
                }

                if (!($lookat_anno_pref || $lookat_sym_pref)) {
                    break;
                }
            }
        }


        $count_matrix[$data["locus_tag"]][0] = $count_matrix[$data["locus_tag"]][0] ? $count_matrix[$data["locus_tag"]][0] : 0;

        echo "  <tr bgcolor='" . $bgcolors[$rowcount % 2] . "'>\n";
        echo "    <td><a href='?task=fetch&query=" . $data["locus_tag"] . "'>" . $data["locus_tag"] . "</a></td>\n";
        echo "    <td>" . $data["contig"] . "</td>\n";
        echo "    <td>" . $data["start"] . "</td>\n";
        echo "    <td>" . $data["stop"] . "</td>\n";
        echo "    <td>" . $kegg_ko . "</td>\n";
        echo "    <td>" . $count_matrix[$data["locus_tag"]][0] . "</td>\n";
        echo "    <td>" . ($count_matrix[$data["locus_tag"]][0] == 0 ? "0" : round ($count_matrix[$data["locus_tag"]][1] / $count_matrix[$data["locus_tag"]][0], 2)) . "</td>\n";
        echo "    <td><small><b>" . $anno_sym_pref . "</b></small> " . $anno_symbol . "</td>\n";
        echo "    <td><center>" . $a_star . "</center></td>\n";
        echo "    <td><small><b>" . $anno_prefix . "</b></small> " . $annotation . "</td>\n";
        echo "  </tr>\n";

        $rowcount++;
    }

    echo "</table>\n";
}

if (!@$_GET["task"]) {
    $_GET["task"] = "all";
}

switch ($_GET["task"]) {
    case "fetch":
        mysql_connect ($my_host, $my_user, $my_pass);
        mysql_select_db ($my_db);

        switch ($_GET["subtask"]) {
            case "makehypo":
                $update_to = "'hypothetical protein'";
                break;
            case "makebaylor":
                $update_to = "baylor";
                break;
            case "makerast":
                $update_to = "rast";
                break;
            case "makeimger":
                $update_to = "img_er";
                break;
            case "makekegg":
                $update_to = "kegg_name";
                break;
            case "makehit":
                $update_to = "'" . addslashes ($_GET["subquery"]) . "'";
                break;
            case "makenullsym":
                $update_symbol = "NULL";
                break;
            case "makebaylorsym":
                $update_symbol = "baylor_symbol";
                break;
            case "makekeggsym":
                $update_symbol = "kegg_symbol";
                break;
            case "makesymbol":
                $update_symbol = "'" . addslashes ($_GET["subquery"]) . "'";
                break;
            case "makenote":
                $update_note = "'" . addslashes ($_GET["subquery"]) . "'";
                break;
        }

        if ($update_to) {
            mysql_query ("UPDATE 20110131_combined_annotation SET curated_name = " . $update_to . " WHERE locus_tag = '" . $_GET["query"] . "' LIMIT 1");
        }

        if ($update_symbol) {
            mysql_query ("UPDATE 20110131_combined_annotation SET curated_symbol = " . $update_symbol . " WHERE locus_tag = '" . $_GET["query"] . "' LIMIT 1");
        }

        if ($update_note) {
            mysql_query ("UPDATE 20110131_combined_annotation SET note = " . $update_note . " WHERE locus_tag = '" . $_GET["query"] . "' LIMIT 1");
        }

        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE locus_tag = '" . addslashes ($_GET["query"]) . "'");
        $data = mysql_fetch_array ($result);

        $this_locus_tag = $data["locus_tag"];

        echo "<big><b>Annotations</b></big><br><br>";

        $setname = "<a href='?task=fetch&subtask=makehypo&query=" . $data["locus_tag"] . "'>Hypothetical</a> | ";
        $setname .= "<a href='?task=fetch&subtask=makebaylor&query=" . $data["locus_tag"] . "'>Baylor</a> | ";
        $setname .= "<a href='?task=fetch&subtask=makerast&query=" . $data["locus_tag"] . "'>RAST</a> | ";

        if (strpos ($data["img_er"], "DUF")) {
            $img_er_split = preg_split ("(\(|\.|\)|\s)", $data["img_er"]);

            $dufs = array ();

            foreach ($img_er_split as $domain) {
                if (substr ($domain, 0, 3) == "DUF") {
                    $dufs[] = $domain;
                }
            }

            sort ($dufs);

            $new_img_er = implode ("/", $dufs) . " domain-containing protein";

            $setname .= "<a href='?task=fetch&subtask=makehit&query=" . $data["locus_tag"] . "&subquery=" . $new_img_er . "'>IMG/ER</a> | ";
        } else {
            $setname .= "<a href='?task=fetch&subtask=makeimger&query=" . $data["locus_tag"] . "'>IMG/ER</a> | ";
        }

        $setname .= "<a href='?task=fetch&subtask=makekegg&query=" . $data["locus_tag"] . "'>KEGG</a><br>";

        $setname .= "<form method='GET' action='?' style='margin-bottom: 0'>";
        $setname .= "<input type='hidden' name='task' value='fetch'>";
        $setname .= "<input type='hidden' name='query' value='" . $this_locus_tag . "'>";
        $setname .= "<input type='hidden' name='subtask' value='makehit'>";
        $setname .= "<input type='input' name='subquery' size='60' value='" . $data["curated_name"] . "'>";
        $setname .= "<input type='submit' value='go'>";
        $setname .= "</form>";

        $setsymbol = "<a href='?task=fetch&subtask=makenullsym&query=" . $data["locus_tag"] . "'>NULL</a> | ";
        $setsymbol .= "<a href='?task=fetch&subtask=makebaylorsym&query=" . $data["locus_tag"] . "'>Baylor</a> | ";
        $setsymbol .= "<a href='?task=fetch&subtask=makekeggsym&query=" . $data["locus_tag"] . "'>KEGG</a><br>";

        $setsymbol .= "<form method='GET' action='?' style='margin-bottom: 0'>";
        $setsymbol .= "<input type='hidden' name='task' value='fetch'>";
        $setsymbol .= "<input type='hidden' name='query' value='" . $this_locus_tag . "'>";
        $setsymbol .= "<input type='hidden' name='subtask' value='makesymbol'>";
        $setsymbol .= "<input type='input' name='subquery' size='60' value='" . $data["curated_symbol"] . "'>";
        $setsymbol .= "<input type='submit' value='go'>";
        $setsymbol .= "</form>";

        $setnote .= "<form method='GET' action='?' style='margin-bottom: 0'>";
        $setnote .= "<input type='hidden' name='task' value='fetch'>";
        $setnote .= "<input type='hidden' name='query' value='" . $this_locus_tag . "'>";
        $setnote .= "<input type='hidden' name='subtask' value='makenote'>";
        $setnote .= "<input type='input' name='subquery' size='60' value='" . $data["note"] . "'>";
        $setnote .= "<input type='submit' value='go'>";
        $setnote .= "</form>";

        if (substr ($data["og"], 0, 3) == "COG") {
            $og_link = "<a href='http://www.ncbi.nlm.nih.gov/COG/grace/wiew.cgi?" . $data["og"] . "' target='_blank'>" . $data["og"] . "</a>";
        } elseif (substr ($data["og"], 0, 3) == "NOG") {
            $og_link = "<a href='http://eggnog.embl.de/cgi_bin/display_single_node.pl?node=" . $data["og"] . "' target='_blank'>" . $data["og"] . "</a>";
        } else {
            $og_link = $data["og"];
        }

        echo "<table cellpadding='4' cellspacing='0' width='700' style='border: 2px solid #A0A0A0'>";
        echo "  <tr style='border: 1px solid #A0A0A0'>";
        echo "    <td width='20%'><b>Locus Tag:</b></td>";
        echo "    <td width='80%'>" . $data["locus_tag"] . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>Note:</b></td>";
        echo "    <td width='80%'>" . $setnote . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>Curated Symbol:</b></td>";
        echo "    <td width='80%'>" . $setsymbol . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>Curated Name:</b></td>";
        echo "    <td width='80%'>" . $setname . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>Baylor:</b></td>";
        echo "    <td width='80%'>" . ($data["baylor"] ? "<b>Symbol:</b> " . ($data["baylor_symbol"] ? $data["baylor_symbol"] : "<i>NULL</i>") . "<br><b>Name:</b> " . $data["baylor"] . "<br><b>EC:</b> " . ($data["baylor_ec_num"] ? "<a href='http://www.expasy.ch/enzyme/" . $data["baylor_ec_num"] . "'>" . $data["baylor_ec_num"] . "</a>" : "<i>NULL</i>") . "<br><b>Localization:</b> " . ($data["baylor_local"] ? $data["baylor_local"] : "<i>NULL</i>") : "<i>NULL</i>") . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>RAST:</b></td>";
        echo "    <td width='80%'>" . $data["rast"] . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>IMG/ER:</b></td>";
        echo "    <td width='80%'>" . $data["img_er"] . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>KEGG:</b></td>";
        echo "    <td width='80%'>" . ($data["kegg"] ? "<b>Symbol:</b> " . $data["kegg_symbol"] . "<br><b>Name:</b> " . $data["kegg_name"] . "<br><b>KO:</b> <a href='http://www.genome.jp/dbget-bin/www_bget?ko:" . $data["kegg"] . "' target='_blank'>" . $data["kegg"] . "</a>" : "<i>NULL</i>") . "</td>";
        echo "  </tr>";
        echo "  <tr>";
        echo "    <td width='20%'><b>OG ID:</b></td>";
        echo "    <td width='80%'>" . ($data["og"] ? $og_link : "<i>NULL</i>") . "</td>";
        echo "  </tr>";
        echo "</table>";

        echo "<br>";

        echo "<big><b>Sequences</b></big><br>";

        echo "<br><a href='#' onClick=\"javascript: forms['CDD'].submit()\">NCBI CDD</a> | <a href='#' onClick=\"javascript: forms['Pfam'].submit()\">Pfam</a>";

        $orf_nt = shell_exec_wrap ($export_nt_cmd . " " . $_GET["query"]);
        $orf_aa = shell_exec_wrap ($export_aa_cmd . " " . $_GET["query"]);

        if ($orf_nt === false || $orf_aa === false) {
            $orf_nt = "Tunnel to NYUMC is down!  Sequences will be unavailable until automated restart in ~5 minutes...";
        }

        echo "<form name='Pfam' method='POST' action='http://pfam.sanger.ac.uk/search/sequence'\" target='_blank'>";
        echo "<input type='hidden' name='seq' value='" . implode ("", array_slice (explode ("\n", $orf_aa), 1)) . "'>";
        echo "<input type='hidden' name='seqOpts' value='both'>";
        echo "<input type='hidden' name='ga' value='0'>";
        echo "<input type='hidden' name='evalue' value='1'>";
        echo "</form>";

        echo "<form name='CDD' method='POST' action='http://www.ncbi.nlm.nih.gov/Structure/cdd/wrpsb.cgi'\" target='_blank'>";
        echo "<input type='hidden' name='seqinput' value='" . implode ("", array_slice (explode ("\n", $orf_aa), 1)) . "'>";
        echo "<input type='hidden' name='db' value='cdd'>";
        echo "<input type='hidden' name='evalue' value='0.01'>";
        echo "<input type='hidden' name='filter' value='F'>";
        echo "<input type='hidden' name='maxhits' value='500'>";
        echo "<input type='hidden' name='mode' value='rep'>";
        echo "</form>";

        echo "<pre>" . $orf_nt . "</pre>";
        echo "<pre>" . $orf_aa . "</pre>";

        echo "<big><b>Top 50 Hits</b></big><br><br>";

        $result = mysql_query ("SELECT id, subject_id FROM " . $my_table . " WHERE query_id = '" . addslashes ($_GET["query"]) . "' GROUP BY subject_id ORDER BY bitscore DESC LIMIT 50");

        echo "<table cellpadding='4' cellspacing='0' width='1200' style='border: 2px solid #A0A0A0'>";
        echo "    <tr bgcolor='#B0B0B0' style='border: 1px solid #A0A0A0; font-size: 0.8em; font-weight: bold'>\n";
        echo "        <td align='center' width='48%'>Subject</td>";
        echo "        <td align='center' width='5%'>#</td>";
        echo "        <td align='center' width='5%'>Ident</td>";
        echo "        <td align='center' width='5%'>Length</td>";
        echo "        <td align='center' width='5%'>Diffs</td>";
        echo "        <td align='center' width='5%'>Gaps</td>";
        echo "        <td align='center' width='5%'>Query<br>Length</td>";
        echo "        <td align='center' width='6%'>Bitscore</td>";
        echo "        <td align='center' width='6%'>E-value</td>";
        echo "        <td align='center' width='5%'>Query Coords</td>";
        echo "        <td align='center' width='5%'>Subject Coords</td>";
        echo "    </tr>";

        $bgcolors = array ("#F0F0F0", "#D0D0D0");
        $rec_count = 0;
        $already_printed = array ();

        while ($data = mysql_fetch_array ($result)) {
            if (in_array (intval ($data["id"]), $already_printed)) { continue; }

            $bracketpos = strpos ($data["subject_id"], " [");

            $firstpart = substr ($data["subject_id"], 0, $bracketpos);
            $nextpart = substr ($data["subject_id"], $bracketpos);

            $result2 = mysql_query ("SELECT * FROM " . $my_table . " WHERE query_id = '" . addslashes ($_GET["query"]) . "' AND subject_id = '" . addslashes ($data["subject_id"]) . "' ORDER BY bitscore DESC LIMIT 4");

            echo "<tr bgcolor='" . $bgcolors[$rec_count % 2] . "' style='border: 1px solid #A0A0A0'>\n";
            echo "    <td rowspan='" . mysql_num_rows ($result2) . "'><a href='?task=fetch&subtask=makehit&query=" . $this_locus_tag . "&subquery=" . urlencode ($firstpart) . "'>" . $firstpart . "</a>" . $nextpart . "</td>";

            $rec_count2 = 0;

            while ($data2 = mysql_fetch_array ($result2)) {
                if ($rec_count2 > 0) {
                    echo "<tr bgcolor='" . $bgcolors[$rec_count % 2] . "' style='border: 1px solid #A0A0A0'>\n";
                }

                echo "        <td align='center'><small>" . $data2["id"] . "</td>";

                foreach (array ("identities", "length", "positives", "gaps") as $key) {
                    $perc = (100 * round ($data2[$key] / $data2["query_length"], 2));

                    $color = "";

                    if ($key == "identities" && $perc > 35) {
                        $new_perc = 100 * (($perc - 35) / 25);

                        for ($i = 0; $i < count ($blues); $i++) {
                            if ($new_perc <= (($i + 1) * (100 / count($blues)))) {
                                $color = $blues[$i];
                                break;
                            }
                        }

                        if ($new_perc > 100) { $color = $blues[count ($blues) - 1]; }
                    } elseif ($key == "identities" && $perc <= 35) {
                        $new_perc = 100 * ($perc / 35);

                        for ($i = 0; $i < count ($reds); $i++) {
                            if ($new_perc <= (($i + 1) * (100 / count($reds)))) {
                                $color = $reds[$i];
                                break;
                            }
                        }
                    }

                    if ($key == "length" && $perc > 70) {
                        $new_perc = 100 * (($perc - 70) / 30);

                        for ($i = 0; $i < count ($blues); $i++) {
                            if ($new_perc <= (($i + 1) * (100 / count($blues)))) {
                                $color = $blues[$i];
                                break;
                            }
                        }

                        if ($new_perc > 100) { $color = $blues[count ($blues) - 1]; }
                    } elseif ($key == "length" && $perc <= 70) {
                        $new_perc = 100 * ($perc / 70);

                        for ($i = 0; $i < count ($reds); $i++) {
                            if ($new_perc <= (($i + 1) * (100 / count($reds)))) {
                                $color = $reds[$i];
                                break;
                            }
                        }
                    }

                    echo "        <td align='center' bgcolor='" . $color . "'><small>" . $data2[$key] . "<br>(" . $perc . "%)</td>";
                }

                if ($data2["evalue"] > $evalue_cutoff) { $eval_color = "#fc9272"; } else { $eval_color = ""; }

                echo "        <td align='center'><small>" . $data2["query_length"] . "</td>";
                echo "        <td align='center'><small>" . $data2["bitscore"] . "</td>";
                echo "        <td align='center' bgcolor='" . $eval_color . "'><small>" . $data2["evalue"] . "</td>";
                echo "        <td align='left'><small><b>s:</b> " . $data2["query_start"] . "<br><b>e:</b> " . $data2["query_end"] . "</td>";
                echo "        <td align='left'><small><b>s:</b> " . $data2["subject_start"] . "<br><b>e:</b> " . $data2["subject_end"] . "</td>";
                echo "    </tr>";

                $rec_count2++;
                $already_printed[] = intval ($data2["id"]);
            }

            $rec_count++;
        }

        echo "</table>";

        break;
    case "all":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "haveko":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NOT NULL ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "donthaveko":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "nohits":
        $nohits = array ();

        foreach ($count_matrix as $key => $value) {
            if ($value[0] == 0) {

                $nohits[] = $key;
            }
        }

        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND locus_tag IN ('" . implode ("', '", $nohits) . "') ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "110hits":
        $nohits = array ();

        foreach ($count_matrix as $key => $value) {
            if ($value[0] > 0 && $value[0] <= 10) {

                $nohits[] = $key;
            }
        }

        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND locus_tag IN ('" . implode ("', '", $nohits) . "') ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "annonoko":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND (baylor NOT LIKE '%hypothetical%' AND baylor IS NOT NULL) AND (rast NOT LIKE '%hypothetical%' AND rast IS NOT NULL) AND (img_er NOT LIKE '%hypothetical%' AND img_er IS NOT NULL) ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "somehypo":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND ((baylor LIKE '%hypothetical%' OR baylor IS NULL) AND (rast LIKE '%hypothetical%' OR rast IS NULL) AND (img_er LIKE '%hypothetical%' OR img_er IS NULL)) ORDER BY locus_tag ASC");

        $exclude = array ();

        while ($data = mysql_fetch_array ($result)) {
            $exclude[] = $data["locus_tag"];
        }

        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND ((baylor LIKE '%hypothetical%' OR baylor IS NULL) OR (rast LIKE '%hypothetical%' OR rast IS NULL) OR (img_er LIKE '%hypothetical%' OR img_er IS NULL)) AND locus_tag NOT IN ('" . implode ("', '", $exclude) . "') ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "allhypo":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND kegg IS NULL AND ((baylor LIKE '%hypothetical%' OR baylor IS NULL) AND (rast LIKE '%hypothetical%' OR rast IS NULL) AND (img_er LIKE '%hypothetical%' OR img_er IS NULL)) ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "nomanual":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND curated_name IS NULL AND curated_symbol IS NULL ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "nosymbol":
        #$result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND baylor_symbol IS NULL AND kegg_symbol NOT REGEXP '^[a-z]{3}[A-Z]*$' AND curated_symbol IS NULL ORDER BY locus_tag ASC");
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND baylor_symbol IS NULL AND (kegg_symbol IS NULL OR kegg_symbol NOT REGEXP '^[a-z]{3}[A-Z]*$') AND curated_symbol IS NULL ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "haveog":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND (kegg IS NOT NULL OR og IS NOT NULL) ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "donthaveog":
        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND (kegg IS NULL AND og IS NULL) ORDER BY locus_tag ASC");
        print_list ($result);
        break;
    case "local":
        $subquery = $_GET["subquery"] == "NULL" ? "IS NULL" : "= '" . addslashes ($_GET["subquery"]) . "'";

        $result = mysql_query ("SELECT * FROM 20110131_combined_annotation WHERE type = 'protein' AND baylor_local " . $subquery . " ORDER BY locus_tag ASC");
        print_list ($result);
        break;
}

?>
