<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

if(isset($_GET['action'])){
    if($_GET['action'] == 'vh_get_new_candidates'){
        echo get_new_candidates_indeed();
    }
}

function get_new_or_stored_token(){
    if(!get_option('vh_new_token_at')){
        update_option('vh_new_token_at', (time() - 200));
    }
    $new_token_time = get_option('vh_new_token_at');
    $now = time();
    if((!$new_token_time) || ($now > $new_token_time)){
        $tokenr = $token = json_decode(new_indeed_token());
        $token = $tokenr->access_token;
        $good_till = $tokenr->expires_in + time();
        update_option('vh_indeed_token', $token);
        update_option('vh_new_token_at', $good_till);
    } else {
        $token = get_option('vh_indeed_token');
    }

    return $token;
}

function get_new_candidates_indeed(){
    $token = get_new_or_stored_token();
    if(isset($_GET['ijobid'])){
        $ijobid = $_GET['ijobid'];
    } else {
        $ijobid = 'e1100148c7a0';
    }

    //only one employer account so no need to offer option of which one to query, for now.
    /*
    $employer_id = get_employer_id();
    $token = get_employer_token($employer_id);
    $token = $token->access_token;
    */

    //echo $employer_id;

    $ch = curl_init();

    $q = '?jobIds='.$ijobid; //.'&employerNumber='.$employer_id;
    curl_setopt($ch, CURLOPT_URL, 'https://employers.indeed.com/api/v2/applications'); //.$q);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


    $headers = array();
    $headers[] = 'Authorization: Bearer '.$token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $result;

}

function new_indeed_token(){

 $client_id = get_option('vh_indeed_client_id');
 $client_se = get_option('vh_indeed_client_se');

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://apis.indeed.com/oauth/v2/tokens?grant_type=client_credentials&scope=employer_access&client_id='.$client_id.'&client_secret='.$client_se);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $headers[] = 'Accept: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $result;
    // returns a string which needs json_decoding the other end.
}

?>
