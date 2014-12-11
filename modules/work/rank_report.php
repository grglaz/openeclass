<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

$require_current_course = true;
require_once '../../include/baseTheme.php';

// Include the main TCPDF library 
require_once __DIR__.'/../../include/tcpdf/tcpdf_include.php';
require_once __DIR__.'/../../include/tcpdf/tcpdf.php';

require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';


if (isset($_GET['assignment'])) {
    global $tool_content, $course_title, $m;
    $as_id = intval($_GET['assignment']);
    $assign = get_assignment_details($as_id);
    $submissions = find_submissions_by_assigment($as_id);
    $i = 1;
    $i++;

	$nameTools = 'Κατατάξεις εργασίας ['. $assign->title. ']';

    
    if($assign==null)
    {
        redirect_to_home_page('modules/work/index.php?course='.$course_code);
    }
    
    $navigation[] = array("url" => "index.php?course=$course_code", "name" => $langWorks);
    $navigation[] = array("url" => "index.php?course=$course_code&amp;id=$as_id", "name" => q($assign->title));

    if (count($submissions)>0) {
        if($assign->auto_judge){// auto_judge enable
      //      $auto_judge_scenarios = unserialize($assign->auto_judge_scenarios);
       //     $auto_judge_scenarios_output = unserialize($sub->auto_judge_scenarios_output);

            if(!isset($_GET['downloadpdf'])){
          //      show_report($as_id, $sub_id, $assign, $submissions, $auto_judge_scenarios, $auto_judge_scenarios_output);
               show_report($assign, $submissions);
               // echo "test";
             draw($tool_content, 2);
            }else{
          
          //download_pdf_file($assign->title, get_course_title(),  q(uid_to_name($sub->uid)), $sub->grade.'/'.$assign->max_grade, $auto_judge_scenarios, $auto_judge_scenarios_output); 
            
               download_pdf_file($course_title,$i,'/'.$assign->max_grade); 
                }
         }
         else{
               Session::Messages(' Ο αυτόματος κριτής δεν είναι ενεργοποιημένος για την συγκεκριμένη εργασία. ', 'alert-danger');
              draw($tool_content, 2);
             }
      } else {
            Session::Messages($m['WorkNoSubmission'], 'alert-danger');
            redirect_to_home_page('modules/work/index.php?course='.$course_code.'&id='.$id);
       }

   } else {
        redirect_to_home_page('modules/work/index.php?course='.$course_code);
    }

// Returns an array of the details of assignment $id
function get_assignment_details($id) {
    global $course_id;
    return Database::get()->querySingle("SELECT * FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $id);
}

// returns an array of the submissions of an assigment
function find_submissions_by_assigment($id) {
	return Database::get()->queryArray("SELECT assignment_submit.grade, assignment_submit.grade_comments, user.username FROM assignment_submit Inner Join user on (user.id=assignment_submit.uid) WHERE assignment_id = ?d", $id);
 
}

function get_course_title() {
    global $course_id;
    $course = Database::get()->querySingle("SELECT title FROM course WHERE id = ?d",$course_id);
    return $course->title;
}

//	function show_report($id, $sid, $assign,$submissions, $auto_judge_scenarios, $auto_judge_scenarios_output) {
function show_report($assign,$submissions) {
    //     global $course_code;
		global $tool_content,$course_code;
           $tool_content = "
                                <table  style=\"table-layout: fixed; width: 99%\" class='table-default'>
                                <tr>
                                     <td><b> Κατάταξη</b></td>
                                     <td><b> Όνομα χρήστη</b></td>
                                     <td><b> Βαθμός</b></td>
                                     <td><b> Test/Περασμένα</b></td>
                                </tr>". get_table_content($assign,$submissions) . "
                                
                                </table>
                                 <p align='left'><a href='rank_report.php?course=".$course_code."&assignment=".$assign->id."&downloadpdf=1'>Λήψη σε μορφή PDF</a></p>
                                <br>";
  }

function get_table_content($assign,$submissions) {
    global $themeimg;
    $table_content = "";
    $i=1;
       
       // Condition about rank position and color of medal
        
if ($i==1 or $i == 2) {$i.=" <img src='../../images/work_medals/Gold_medal_with_cup.svg'  width='30px' height='30px'>";}                                        
if ($i==3 or $i == 4) {$i.=" <img src='../../images/work_medals/Silver_medal_with_cup.svg'  width='30px' height='30px'>";}     
if ($i==5 or $i == 6) {$i.=" <img src='../../images/work_medals/Bronze_medal_with_cup.svg'  width='30px' height='30px'>";} 

 // End of Condition about rank position and color of medal    
    
    foreach($submissions as $submission){
//                     $icon = ($auto_judge_scenarios_output[$i]['passed']==1) ? 'tick.png' : 'delete.png';
                     $table_content.="
                                      <tr>
                                      <td style=\"word-break:break-all;\">".$i."</td>
                                      <td style=\"word-break:break-all;\">".$submission->username."</td>
                                      <td style=\"word-break:break-all;\">".$submission->grade."/". $assign->max_grade  ."</td>
                                      <td align=\"center\">".$submission->grade_comments."</td></tr>";
                     $i++;
                }
    return $table_content;
  }
  
//function download_pdf_file($assign_title, $course_title,  $username, $grade, $auto_judge_scenarios, $auto_judge_scenarios_output){ 

function download_pdf_file($course_title,$i,$grade){ 
    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle('Auto Judge Report');
    $pdf->SetSubject('Auto Judge Report');
    // set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // add a page
    $pdf->AddPage();

$i=0;
$i++;

 $report_details ='
        <style>
    table.first{
        width: 100%;
        border-collapse: collapse;
         vertical-align: center;
    }

    td {
        font-size: 1em;
          border: 1px solid #000000;
        padding: 3px 7px 2px 7px;
         text-align: center;
    }

     th {
        font-size: 1.1em;
        text-align: left;
        padding-top: 5px;
        padding-bottom: 4px;
        background-color: #3399FF;
        color: #ffffff;
        width: 120px;
           border: 1px solid #000000;
    }
    </style>

        <table class="first">
            <tr>
            <th> Rank</th> <td>'.$i.'</td>
            </tr>
             <tr>
            <th> Εκπαιδευόμενος </th> <td> ??</td>
            </tr>
             <tr>
            <th> Βαθμός</th><td> ?? </td>
            </tr>
             <tr>
            <th> Περασμένα Σενάρια</th> <td>'.$grade.'</td>
            </tr>                  
    </table>';
                               

    $pdf->writeHTML($report_details, true, false, true, false, '');
    $pdf->Ln();     
    $pdf->Output('Rank Report for Lesson' .$course_title.'.pdf', 'D');
}
