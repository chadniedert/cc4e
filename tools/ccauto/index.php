<?php

require_once './class.Diff.php';
require_once "../config.php";
require_once "../../play_util.php";
require_once "../../sandbox/sandbox.php";
require_once "ccauto_util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;
use \Tsugi\UI\Lessons;
use \Tsugi\Grades\GradeUtil;

$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $LAUNCH->user->displayname;
$email = $LAUNCH->user->email;

$LOGGED_IN = true;
$RANDOM_CODE = getLinkCode($LAUNCH);
$RANDOM_CODE_HOUR = getLinkCodeHour($LAUNCH);

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// All the assignments we support
$assignments = array(
    '1-01-hello.php' => '1-1 Hello world (of course)',
    'rs-01-output.php' => 'rs-1 Produce Output',
    'rs-02-input.php' => 'rs-2 Read Input',
    'rs-03-io.php' => 'rs-3 Read Input and Produce Output',
    'rs-04-line.php' => 'rs-4 Read An Entire Line',
    'rs-05-gets.php' => 'rs-5 Read An Entire Line Using fgets',
    'rs-06-for.php' => 'rs-6 Write a simple for loop',
    'rs-07-minmax.php' => 'rs-7 Compute min/max',
    'rs-08-guess.php' => 'rs-8 Guesing game',
    'rs-09-function.php' => 'rs-9 Write a function',
    'rs-10-concat.php' => 'rs-10 String concatenation',
    'rs-11-rstrip.php' => 'rs-11 Implement rstrip()',
    'rs-12-lstrip.php' => 'rs-12 Implement lstrip()',
    'lb-04-syntax.php' => 'LBS280-4 Fix syntax errors',
    'lb-08-average.php' => 'LBS280-8 Average five numbers',
    'lb-13-calcpay.php' => 'LBS280-13 Function calcpay',
    'lb-14-arrays.php' => 'LBS280-14 Array of integers',
    'lb-15-string.php' => 'LBS280-15 Array of characters',
    'lb-16-calculator.php' => 'LBS280-16 Calculator',
    'lb-17-touring.php' => 'LBS280-17 A Touring Machine',
    'lb-18-machine.php' => 'LBS280-18 Machine Language I',
    'lb-98-touring.php' => 'LBS280-98 A Touring Machine',
    '1-03-heading.php' => '1-3 Add a header',
    '1-04-celsius.php' => '1-4 Celsius / Fahrenheit Table',
    '1-05-reverse.php' => '1-5 Reversed Fahrenheit / Celsius Table',
    '1-06-count.php' => '1-6 Count blanks, and newlines',
    '1-07-single.php' => '1-7 Multiple spaces to a single space',
    '1-10-words.php' => '1-10 Print the words in a file',
    '1-17-reverse.php' => '1-17 A function to reverse a string',
    '2-02-htoi.php' => '2-2 Write a function convert from base -16 (hex) to base-10',
    '2-10-lower.php' => '2-10 Write a function convert a character to lower case',
    '3-01-expand.php' => '3-1 Write a function to expand non-printing characters',
    '3-04-itob.php' => '3-4 Write itob() and itoh()',
    '3-06-uniq.php' => '3-6 Write simple version of UNIX uniq',
    '4-A-faren.php' => '4-A Write a function to convert from Celsius to Fahrenheit',
    '4-B-extern.php' => '4-B Write a function that uses external data scope',
    '4-C-static.php' => '4-C Write a function that uses static data scope',
    '6-A-list.php' => '6-A ArrayList of Integers',
    '6-B-delete.php' => '6-B Insert and delete linked list',
    'cc_03_02-pystr.php' => 'Implement the pystr class (cc_03_02)',
    'cc_03_03-pylist.php' => 'Implement the pylist class (cc_03_03)',
    'cc_03_04-pydict.php' => 'Implement the pydict class (cc_03_04)',
    'cc_04_01-encapsulation.php' => 'Implement a list-based Map class (cc_04_01)',
    'cc_04_02-iterator.php' => 'Add an Iterator to the list-based Map class (cc_04_02)',
    'cc_05_01-hash.php' => 'Implement HashMap (cc_05_01)',
    'cc_05_02-tree.php' => 'Implement LinkedTreeMap (cc_05_02)',
    'cc_05_03-words.php' => 'Count words (cc_05_03)',
    'p-02-03-pay.php' => '2.3 Gross pay (no overtime)',
    'p-03-01-payroll.php' => '3.1 Time and a half for overtime',
    'p-03-04-score.php' => '3.4 Score with else if',
    'p-04-06-function.php' => '4.6 Write computepay() function',
    'p-05-02-maxmin.php' => '5.2 Max / min numbers',
);

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

$custom = LTIX::ltiCustomGet('exercise');

if ( $assn && isset($assignments[$assn]) ) {
    // Configured
} else if ( strlen($custom) > 0 && isset($assignments[$custom]) ) {
    Settings::linkSet('exercise', $custom);
    $assn = $custom;
}

if ( $assn === false && isset($_GET["inherit"]) && isset($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
    if ( $l ) {
        $lti = $l->getLtiByRlid($_GET['inherit']);
        if ( isset($lti->custom) ) foreach($lti->custom as $custom ) {
            if (isset($custom->key) && isset($custom->value) && $custom->key == 'exercise' ) {
                $assn = $custom->value;
                Settings::linkSet('exercise', $assn);
            }
        }
    }
}

// Get any due date information
$dueDate = SettingsForm::getDueDate();

$menu = false;
if ( $LAUNCH->user->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    $menu->addLeft(__('Grade Detail'), 'grades.php');
    $menu->addLeft('Send Grade', 'sendgrade.php');
    $menu->addRight(__('Launches'), 'analytics');
    $menu->addRight(__('Settings'), '#', /* push */ false, SettingsForm::attr());
}

// Load up the assignment
if ( $assn && isset($assignments[$assn]) ) {
    include($assn);
    $ASSIGNMENT = true;
    $instructions = ccauto_instructions($LAUNCH);
    $sample = ccauto_sample($LAUNCH);
    $solution = ccauto_solution($LAUNCH);
    $input = ccauto_input($LAUNCH);
    $output = ccauto_output($LAUNCH);
    $prohibit = ccauto_prohibit($LAUNCH);
    $require = ccauto_require($LAUNCH);
    $main = ccauto_main($LAUNCH);
}

$stdout = False;
$stderr = False;

if ( isset($_POST['reset']) ) {
    unset($_SESSION['retval']);
    unset($_SESSION['code']);
    unset($_SESSION['actual']);
    GradeUtil::gradeUpdateJson(json_encode(array("code" => null)));
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if ( isset($_POST['code']) ) {
    unset($_SESSION['retval']);
    $_SESSION['code'] = U::get($_POST, 'code', false);
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

$code = U::get($_SESSION, 'code');
$retval = U::get($_SESSION, 'retval');
$actual = U::get($_SESSION, 'actual');
$prohibit_results = null;
$require_results = null;
$graderet = false;

if ( $retval == NULL && is_string($code) && strlen($code) > 0 ) {
   $succinct = preg_replace('/\s+/', ' ', $code);
   $prohibit_results = check_prohibit($code, $prohibit);
   $require_results = check_require($code, $require);
   if ( is_array($prohibit_results) || is_array($require_results) ) {
        $_SESSION['retval'] = false;
        GradeUtil::gradeUpdateJson(json_encode(array("code" => $code)));
    } else {
        error_log("Autograde by ".$displayname.' '.$email.': '.substr($succinct,0, 250));
        $retval = cc4e_compile($code, $input, $main);
        GradeUtil::gradeUpdateJson(json_encode(array("code" => $code)));
        $_SESSION['retval'] = $retval;
        $actual = isset($retval->docker->stdout) && strlen($retval->docker->stdout) > 0 ? $retval->docker->stdout : false;
        $_SESSION['actual'] = $actual;
        if ( is_string($actual) && is_string($output) && trim($actual) == trim($output) ) {
            $grade = 1.0;
            $debug_log = array();
            $graderet = LTIX::gradeSend($grade, false, $debug_log);
            error_log("Success: ".$displayname.' '.$email);
            // $OUTPUT->dumpDebugArray($debug_log);
	}
    }
}

$output_compare_fail = is_string($actual) && is_string($output) && trim($actual) != trim($output);

$row = GradeUtil::gradeLoad();
$json = array();
if ( $row !== false && isset($row['json'])) {
    $json = json_decode($row['json'], true);
}

$code = '';
if ( !is_string($code) || strlen($code) < 1 ) {
    if ( isset($json["code"]) ) {
        $code = $json["code"];
    } else if ( isset($sample)) {
        $code = $sample;
    }
}

$lines = $code ? count(explode("\n", $code)) : 15;
if ( $lines < 10 ) $lines = 10;

// View
$OUTPUT->header();
cc4e_play_header($lines);

// https://code.iamkate.com/php/diff-implementation/
?>
<style>
    .diff table
    {
        table-layout: fixed;
        width: 100px;
    }

      .diff td{
        padding:0 0.667em;
        vertical-align:top;
        white-space:pre;
        white-space:pre-wrap;
        font-family:Consolas,'Courier New',Courier,monospace;
        font-size:1.05em;
        line-height:1.333;
      }
      .diff th{
        padding:0.2em 0.667em;
        vertical-align:top;
        white-space:pre;
        white-space:pre-wrap;
        font-family:Consolas,'Courier New',Courier,monospace;
        font-size:1.05em;
        line-height:1.333;
      }
      .diff td:first-child{
        width: 50%;
        max-width: 200px;
        overflow: hidden;
      }
table td { border-left: 1px solid #000; padding: 2px;}
table th { border-left: 1px solid #000; padding: 2px;}

      .diff span{
        display:block;
        min-height:1.333em;
        margin-top:-1px;
        padding:0 3px;
      }

      * html .diff span{
        height:1.333em;
      }

      .diff span:first-child{
        margin-top:0;
      }

      .diffDeleted span{
        border:1px solid rgb(255,192,192);
        background:rgb(255,224,224);
      }

      .diffInserted span{
        border:1px solid rgb(192,255,192);
        background:rgb(224,255,224);
      }

      #toStringOutput{
        margin:0 2em 2em;
      }
.max_box {
  position:absolute;
  background: white;
  top:0px;
  right:20px;
  bottom:0px;
  left:20px;
}

</style>

<?php

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

SettingsForm::start();
SettingsForm::select("exercise", __('Please select an assignment'),$assignments);
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

if ( isset($_SESSION['error']) ) {
    $RESULT->setNote($_SESSION['error']);
} else if ( isset($_SESSION['success']) ) {
    $RESULT->setNote($_SESSION['success']);
}

$OUTPUT->flashMessages();

if ( ! (isset($ASSIGNMENT) && $ASSIGNMENT) ) {
    if ( $LAUNCH->user->instructor ) {
        echo("<p>Please use settings to select an assignment for this tool.</p>\n");
    } else {
        echo("<p>This tool needs to be configured - please see your instructor.</p>\n");
    }
    $OUTPUT->footer();
    return;
}


echo("<p>".$instructions."</p>\n");
?>
<div id="editor_panel">
<form method="post">
<p>
<input type="submit" name="run" onclick="startRun();" value="Run Code" disabled id="runcode">
<input type="submit" name="reset" value="Reset Code"
    onclick="return confirm('Do you really want to reset the code to the default?');"
>
<input type="submit" style="float:right;display:none;" value="Max" id="max_min"
    onclick="toggleMax();return false;"
>
<span id="runstatus"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"/></span>
<span id="editstatus" style="display: none;">Edit code below:</span>
<?php
column_row_start();
column_editor_start();
?>
<?php
$errors = cc4e_play_errors($retval);
cc4e_play_inputs($lines, $code);

column_editor_end();
column_io_start();
?>
<?php
if ( is_string($input) && strlen($input) > 0 ) {
?>
<p>This will be provided as input to your program:
<a href="#" onclick="copyToClipboardNoScroll(this, $('#programinput').text(), true);return false;">Copy</a>
</p>
<p>
<div id="programinput" class="pre_text"><pre>
<?php
    echo(htmlentities($input));
?>
</pre></div>
</p>
<?php } ?>
<?php

    // https://code.iamkate.com/php/diff-implementation/
    $diff_shown = false;

    if ( is_array($prohibit_results) ) {
        echo '<p style="color:red;">NOT GRADED: '.$prohibit_results[0].'</p>'."\n";
        error_log("Prohibited: ".$displayname.' '.$email.': '.$prohibit_results[0]);
    } else if ( is_array($require_results) ) {
        echo '<p style="color:red;">NOT GRADED: '.$require_results[0].'</p>'."\n";
        error_log("Required: ".$displayname.' '.$email.': '.$require_results[0]);
    } else if ( is_string($actual) && is_string($output) ) {
        if ( trim($actual) == trim($output) ) {
            if ( $graderet == true ) {
                echo('<p style="color:green;">OUTPUT MATCH - Grade sent to server</p>'."\n");
            } else if ( is_string($graderet) ) {
                echo('<p style="color:red;">OUTPUT MATCH - Grade not sent: '.$graderet."</p>\n");
            }
        } else {
            echo '<p style="color:red;">Output does not match</p>'."\n";
            $diff = Diff::compare(trim($output), trim($actual));
            echo('<div style="border: 1px solid black;">');
            $table = Diff::toTable($diff);
            $header = '<tr class="header"><th>Expected Output</th><th>Your Output</th></tr>';
            $table = str_replace('<table class="diff">', '<table class="diff" id="difftable">'.$header, $table);
            $table = str_replace('<br>', '', $table);
            echo($table);
            echo('</div>');
	    $diff_shown = true;
	}
    } else if (is_string($output) ) {
    	echo '<div style="color: green;">'."\n";
    	echo "Expected output from your program:\n\n";
    	echo('<div id="expectedoutput" class="xpre_text"><pre>');
    	echo(htmlentities($output, ENT_NOQUOTES));
    	echo("</pre></div>\n");
    	echo("</div>\n");
    }


if ( ! $diff_shown) cc4e_play_output($retval);
column_io_end();
?>
</form>
<?php
column_main_start();

if ( is_string($main) && strlen($main) > 0 ) {
?>
<div style="color: blue;">
The main program which will execute your code:
<div id="mainprogram" class="xpre_text"><pre id="mainpre">
<?php    echo(htmlentities($main, ENT_NOQUOTES)); ?>
</pre></div>
<div>
<?php
}

column_main_end();
column_row_end();

echo('<div class="row">'."\n");
echo('</div>  <!-- class="row" -->'."\n");
cc4e_play_debug($retval);

?>
<p style="margin-top: 1em;">
This compiler uses a pretty complex docker setup to run your code - you
might get  "docker error" or a "timeout" if there is a problem with the
compiler environment.  Usually you can just re-try a
compile and it will work.  There is a
<a href="https://status.cc4e.com/" target="_blank">status page</a>
that runs a test every minute or two on this site and monitors the reliability
of its C compiler.
</p>
<?php

if ( $LAUNCH->user->instructor && is_string($solution) && strlen($solution) > 0 ) {
?>
<div><p>Solution: (instructor only)
<a href="#" onclick="copyToClipboardNoScroll(this, $('#instructor_solution').text(), true);return false;">Copy</a>
</p>
<pre id="instructor_solution">
<?php echo(htmlentities($solution, ENT_NOQUOTES)); ?>
</pre></div>
<?php
}

$OUTPUT->footerStart();
cc4e_play_footer();
?>
<script>
$(document).ready( function() {
    document.getElementById("body_container").className = "container-fluid";
    var width = $('#difftable').parent().width();
    console.log('yada', width);
    $('td:first-child').width(width/2);
    // $('.diff td:first-child').each(function(pos, tag) {
    $('.diff td').each(function(pos, tag) {
        var tdw = (width/2)+'px';
        console.log('tag', pos, tag, width, tdw);
        $(tag).css('width', tdw);
        $(tag).css('max-width', tdw);
        $(tag).css('min-width', tdw);
        $(tag).css('overflow', 'hidden');

    });
    $(window).resize(function() {
         window.console && console.log('.resize() called. width='+
           $(window).width()+' height='+$(window).height());
     });
});
function startRun() {
	$("#runstatus").show();
	$("#editstatus").hide();
}
function toggleMax() {
    var editor_panel = document.getElementById("editor_panel");
    if ( editor_panel.classList.contains('max_box') ) {
        editor_panel.classList.remove('max_box');
        editor_panel.style.top = "0px";
        document.getElementById("max_min").value = 'Max';
        return;
    }

    var offsets = document.getElementById('body_container').getBoundingClientRect();
    console.log(offsets);
    var top = offsets.top;
    editor_panel.classList.add("max_box");
    editor_panel.style.top=Math.trunc(offsets.top)+"px";
    document.getElementById("max_min").value = 'Inline';
}
</script>
<?php
$OUTPUT->footerEnd();

