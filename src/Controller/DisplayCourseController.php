<?php

namespace Drupal\frocole\Controller;

/**
 * A route for displaying a course.
 *
 * @category View
 * @package Drupal\frocole\Controller
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Show a Course.
 *
 * @category Displaycontroller
 * @package Drupal\frocole\Controller
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class DisplayCourseController extends ControllerBase {

  /**
   * Show a Course.
   *
   * @param int $id
   *   The Course ID.
   *
   * @return form
   *   The form of courses to be rendered as a table.
   */
  public function show($id) {
    // See https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21Database.php/function/Database%3A%3AgetConnection/8.9.x
    // See https://api.drupal.org/api/drupal/core%21lib%21Drupal.php/function/Drupal%3A%3Adatabase/8.2.x
    $conn = Database::getConnection('default', 'frocole');

    $query = $conn
      ->select('courses', 'c')
      ->condition('c.CourseID', $id);

    // See https://www.drupal.org/docs/8/api/database-api/dynamic-queries/joins
    $query
      ->join('users', 'u', 'c.LeraarUserID=u.UserID');
    $query
      ->join('segments', 's', 'c.SegmentID=s.SegmentID');

    $query
      ->fields('c')
      ->fields('u', ['Username'])
      ->fields('s', ['SegmentName']);

    $data = $query
      ->execute()
      ->fetchAssoc();

    // [Courses]
    $course_name = $data['CourseName'];
    $ipf = $data['IPF_RD_parameters'];
    $gpf = $data['GPF_RD_parameters'];
    $segmentID = $data['SegmentID'];
    $leraarID = $data['LeraarUserID'];
    $active = $data['CourseActive'];

    // [Leraar/Segment]
    $leraar = $data['Username'];
    $segment = $data["SegmentName"];

    // [Groups]
    $query = $conn
      ->select('groups', 'g')
      ->condition('g.CourseID', $id)
      ->fields('g');
    $data = $query
      ->execute()
      ->fetchAllAssoc('GroupID', \PDO::FETCH_ASSOC);

    // [Groups]
    $groups = "";

    foreach ($data as $record) {
      // Do something with each $record.
      $groupID = $record['GroupID'];
      $group = $record['GroupNickname'];

      $export_url = Url::fromRoute('frocole.export_form', ['id' => $groupID], []);

      $groups .= '<tr><td>[<a href="' . $export_url->toString() . '" title="' . t('Export feedback to CSV/Excel') . '">' . str_pad($groupID, 4, '0', STR_PAD_LEFT) . '</a>]</td><td>' . $group . '</td><td>' . $this->fetchGroupUsers($conn, $groupID) . '</td></tr>';
      // $groups .= $this->fetchGroupUsers($conn, $groupID);
    }

    if (strlen($groups) === 0) {
      $groups = "<li><i>" . t('No Groups') . "</i>";
    }

    $groups = "<table><tr><th>GroupID</th><th>Group Nickname</th><th>Users</th></tr>" . $groups . "</table>";

    $url = Url::fromRoute('frocole.display_courses');

    return [
      '#type' => 'markup',
      '#markup' =>
      "<a href='" . $url->toString() . "'>" . t('Manage Courses') . "</a><h1>$course_name</h1><br><strong>IPF_RD</strong><p>" .
      $this->axisToList($ipf) . "</p><strong>GPF_RD</strong><p>" .
      $this->axisToList($gpf) . "</p><strong>" . t('Segment') . "</strong><p>[" .
      str_pad($segmentID, 4, '0', STR_PAD_LEFT) . "]&nbsp;$segment</p><strong>" . t('Teacher') . "</strong><p>[" .
      str_pad($leraarID, 4, '0', STR_PAD_LEFT) . "]&nbsp;$leraar</p><strong>" . t('Active') . "</strong><p>$active</p><strong>" . t('Groups') . "</strong><p>$groups</p>",
    ];
  }

  /**
   * Converts a list of axis labels to a table.
   *
   * @param string $pf
   *   The axis labels.
   *
   * @return html
   *   List with all PFRD parameters.
   */
  private function axisToList($pf) {
    return "<table><tr><td>|</td><td>" .
        str_replace('/', "</td><td>|</td><td>", $pf) .
        "</td><td>|</td></tr></table>";

    // Return str_replace('/', '&nbsp;|&nbsp;', $pf);.
  }

  /**
   * Fetches the Users of a Group.
   *
   * @param connection $conn
   *   The Database Connection.
   * @param int $groupID
   *   The ID of the Group to be retrieved.
   *
   * @return string
   *   list containing all users and their id's of a group.
   */
  private function fetchGroupUsers($conn, $groupID) {
    // [Groups]
    $query = $conn
      ->select('userandgrouprelations', 'r');

    $query->join('users', 'u', 'r.UserID=u.UserID');
    $query->condition('r.GroupID', $groupID);
    $query
      ->fields('r')
      ->fields('u', ['Username']);

    $data = $query
      ->execute()
      ->fetchAllAssoc('UserID', \PDO::FETCH_ASSOC);

    // [Users]
    $users = "";

    foreach ($data as $record) {
      // Do something with each $record.
      $userID = $record['UserID'];
      $user = $record['Username'];
      $users .= "<tr>";
      $users .= "<td>" . $userID . "</td><td>" . $user . "</td>";
      $users .= "</tr>";
    }

    if (strlen($users) === 0) {
      $users .= "<tr>";
      $users .= "<td span='2'>" . t('No Users') . "</td>";
      $users .= "<tr>";

      $users = "<table>" . $users . "</table>";
    }
    else {
      $users = "<table><tr><th>UserID</th><th>Username</th></tr>" . $users . "</table>";
    }

    return $users;
  }

}
