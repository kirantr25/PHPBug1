<?php

// site.php - Interface to the site table
// ------------------------------------------------------------------------
// Copyright (c) 2001, 2002 The phpBugTracker Group
// ------------------------------------------------------------------------
// This file is part of phpBugTracker
//
// phpBugTracker is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// phpBugTracker is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with phpBugTracker; if not, write to the Free Software Foundation,
// Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
// ------------------------------------------------------------------------
// $Id: site.php,v 1.3 2004/10/25 12:06:59 bcurtis Exp $

chdir('..');
define('TEMPLATE_PATH', 'admin');
include 'include.php';

function del_item($siteid = 0) {
    global $db, $me;

    if ($siteid) {
        // Make sure we are going after a valid record
        $itemexists = $db->getOne('select count(*) from ' . TBL_SITE .
                " where site_id = $siteid");
        // Are there any bugs tied to this one?
        $bugcount = $db->getOne('select count(*) from ' . TBL_BUG .
                " where site_id = $siteid");
        if ($itemexists and ! $bugcount) {
            $db->query('delete from ' . TBL_SITE . " where site_id = $siteid");
        }
    }
    header("Location: $me?");
}

function do_form($siteId = 0) {
    global $db, $me, $t;

//    var_dump($_POST);
//    die();
//    extract($_POST);
//    array (size=6)
//      'site_name' => string 'ddd' (length=3)
//      'sort_order' => string '3' (length=1)
//      'Submit' => string 'Отправить' (length=18)
//      'site_id' => string '' (length=0)
//      'use_js' => string '1' (length=1)
//      'op' => string 'save' (length=4)

    $error = '';
    $siteName = trim(get_post_val('site_name', null)); // <script>alert('q');</script>
    $sortOrder = get_post_int('sort_order', 0);
    $useJs = get_request_int('use_js', 0);
    // Validation
    if ($siteName == '') {
        $error = translate("Please enter a name");
    }
    if ($error) {
        show_form($siteId, $error);
        return;
    }

    if (!$siteId) {
        $db->query('insert into ' . TBL_SITE . ' (site_id, site_name, sort_order) values (' . $db->nextId(TBL_SITE) . ', ' . $db->quote(($siteName)) . ', ' . $sortOrder . ')');
    } else {
        $db->query('update ' . TBL_SITE . ' set site_name = ' . $db->quote(($siteName)) . ', sort_order = ' . $sortOrder . ' where site_id = ' . $siteId);
    }
    if ($useJs) {
        $t->render('edit-submit.html');
    } else {
        header("Location: $me?");
    }
}

function show_form($siteId = 0, $error = '') {
    global $db, $me, $t;
    $useJs = get_request_int('use_js', 0);

    if ($siteId && !$error) {
        $t->assign($db->getRow("select * from " . TBL_SITE . " where site_id = '$siteId'"));
    } else {
        $t->assign($_POST);
    }
    $t->assign('error', $error);
    $t->assign('useJs', $useJs);
    $t->render('site-edit.html.php', translate("Edit Site"), ($useJs == 1) ? 'wrap-popup.php' : 'wrap.php');
}

function list_items($siteid = 0, $error = '') {
    global $me, $db, $t, $QUERY;

    $rOrder = get_request_value('order', 'sort_order');
    $order = preg_replace("/[^a-zA-Z0-9_]+/", "", $rOrder);
    $sort = get_request_value('sort', 'asc');
    if (!in_array($sort, array('asc', 'desc'))){
        $sort = 'asc';
    }
    $page = get_get_int('page', 1);

    $nr = $db->getOne("select count(*) from " . TBL_SITE);

    list($selrange, $llimit) = multipages($nr, $page, "order=$order&sort=$sort");

    $t->assign('sites', $db->getAll($db->modifyLimitQuery(
                            sprintf($QUERY['admin-list-sites'], $order, $sort), $llimit, $selrange)));

    $headers = array(
        'siteid' => 'site_id',
        'name' => 'site_name',
        'sortorder' => 'sort_order');

    sorting_headers($me, $headers, $order, $sort);

    $t->render('sitelist.html.php', translate("Site List"));
}

$perm->check('Admin');

if (isset($_REQUEST['op'])) {
    switch ($_REQUEST['op']) {
        case 'add' : list_items();
            break;
        case 'edit' : show_form(get_get_int('site_id', null));
            break;
        case 'del' :
            if (check_action_key_die()) {
                del_item(get_get_int('site_id'));
            }
            break;
        case 'save' : do_form(get_post_int('site_id', null));
            break;
    }
} else {
    list_items();
}

//