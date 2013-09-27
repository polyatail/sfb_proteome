<title>blastx: SFB vs NR</title>

<big><big><big>blastx: SFB vs NR</big></big></big>
<hr>

<a href="?task=all">All SFB ORFs</a> |
<a href="?task=haveko">Annotated by KAAS</a> |
<a href="?task=nohits">No KO, No Hits in NR</a> |
<a href="?task=110hits">No KO, 1-10 hits in NR</a> |
<a href="?task=needcuration">No KO, Need Manual Curation</a> |
<a href="?task=hypothetical">No KO, Likely Hypothetical</a>

<br><br>

<?

$my_host = "littman60";
$my_user = "root";
$my_pass = "aids";
$my_db = "sfb";
$my_table = "20101228_blastx_sfb_nr";

$file_sfb_nt = "/comp_node0/andrew/proj/sfb/20101102_baylor_annotation/SFB.454AllContig.20101002.protein.formatted.singleline.fa";
$file_sfb_aa = "/comp_node0/andrew/proj/sfb/20101102_baylor_annotation/SFB.454AllContig.20101002.protein.formatted.singleline.faa";

$prefix = "/comp_node0/andrew/proj/sfb/20101230_kaas_annotation/";

$file_all = "combined_fields.txt";
$file_haveko = "20101230_orfs_with_kos.txt";
$file_nohits = "20101230_orfs_with_no_hits_in_nr.txt";
$file_110hits = "20101230_orfs_with_1-10_hits_in_nr.txt";
$file_likelyhypo = "20101230_orfs_likely_hypothetical.txt";
$file_needcuration = "20101230_orfs_need_manual_curation.txt";

function print_list ($thelist) {
    global $prefix;

    $fp = file (realpath ($prefix . "/" . $thelist));

    echo "<pre>";

    echo "<b>SFB ORF Name                                KO #           # Hits         % Predicted    Baylor Annotation</b>\n";

    foreach ($fp as $line) {
        $line_split = explode (" ", $line);

        echo "<a href=\"?task=fetch&query=" . urlencode ($line_split[0]) . "\">" . $line_split[0] . "</a>" . implode (" ", array_slice ($line_split, 1));
    }

    echo "</pre>";
}

if (!@$_GET["task"]) {
    $_GET["task"] = "all";
}

switch ($_GET["task"]) {
    case "fetch":
        echo "<big>Sequences</big><br>";

        $orf_nt = shell_exec ("grep -A1 -m1 " . $_GET["query"] . " " . $file_sfb_nt . " | fasta_formatter -w 60");
        echo "<pre>" . $orf_nt . "</pre>";

        $orf_aa = shell_exec ("grep -A1 -m1 " . $_GET["query"] . " " . $file_sfb_aa . " | fasta_formatter -w 60");
        echo "<pre>" . $orf_aa . "</pre>";

        echo "<big>Top 20 Hits</big><br>";

        mysql_connect ($my_host, $my_user, $my_pass);
        mysql_select_db ($my_db);

        $result = mysql_query ("SELECT * FROM " . $my_table . " WHERE query_id = '" . addslashes ($_GET["query"]) . "' ORDER BY bitscore DESC, (identities / length) DESC, (positives / length) DESC, gaps ASC, evalue ASC LIMIT 20");

        echo "<table>";

        $first_row = true;
        $rec_count = 0;

        while ($data = mysql_fetch_array ($result, MYSQL_ASSOC)) {
            if ($first_row == true) {
                echo "<tr>\n";

                foreach (array_keys ($data) as $item) {
                    print "<td>" . $item . "</td>\n";
                }

                echo "</tr>\n";

                $first_row = false;
            }

            $bgcolor = $rec_count % 2 == 0 ? "gray" : "lightgray";

            echo "<tr bgcolor='$bgcolor'>\n";

            foreach ($data as $item) {
                print "<td>" . $item . "</td>\n";
            }

            echo "</tr>\n";

            $rec_count++;
        }

        echo "</table>";

        break;
    case "all":
        print_list ($file_all);
        break;
    case "haveko":
        print_list ($file_haveko);
        break;
    case "nohits":
        print_list ($file_nohits);
        break;
    case "110hits":
        print_list ($file_110hits);
        break;
    case "hypothetical":
        print_list ($file_likelyhypo);
        break;
    case "needcuration":
        print_list ($file_needcuration);
        break;
}

?>
