<?php

/**
 * Activity Type data model
 */
class ActivityType extends DataMapper {

var $table    = 'view_cmp_gisactivitytype';

// @TODO: Move this into the DB
var $chosen_activities = array(
    6, // 'Archery'
    1, // 'Biking & Cycling'
    3, // 'Boating, Sailing & Paddlesports'
    7, // 'Cross-Country Skiing'
    21, // 'Dining'
    //39, // 'Exercising'
    20, // 'Exploring Culture & History'
    19, // 'Exploring Nature'
    5, // 'Fishing & Ice Fishing'
    //25, // 'Fitness Circuit'
    9, // 'Geocaching'
    30, // 'Golfing'
    4, // 'Hiking & Walking'
    11, // 'Horseback Riding'
    12, // 'Mountain Biking'
    13, // 'Picnicking'
    18, // 'Rope Courses & Zip Lines'
    15, // 'Sledding'
    16, // 'Snowshoeing'
    2, // 'Swimming'
    //17, // 'Tobogganing'
);
var $activity_type_icons_by_id = array(
     1 => 'bike.svg',      // Biking & Cycling
     2 => 'swim.svg',      // Swimming
     3 => 'boat.svg',      // Boating, Sailing & Paddlesports
     4 => 'hike.svg',      // Hiking & Walking
     5 => 'fish.svg',      // Fishing & Ice Fishing
     6 => 'archery.svg',   // Archery
     7 => 'xcski.svg',     // Cross-Country Skiing
     9 => 'geocache.svg',  // Geocaching
    11 => 'horse.svg',     // Horseback Riding
    12 => 'mtnbike.svg',   // Mountain Biking
    13 => 'picnic.svg',    // Picnicking
    14 => '',              // Races & Competitions
    15 => 'sled.svg',      // Sledding
    16 => 'snowshoe.svg',  // Snowshoeing
    17 => '',              // Tobogganing
    18 => 'leafman.svg',   // Rope Courses & Zip Lines
    19 => 'geology.svg',   // Exploring Nature
    20 => 'history.svg',   // Exploring Culture & History
    21 => 'dine.svg',      // Dining
    22 => '',              // Classes, Workshops, & Lectures
    23 => 'leafman.svg',   // Special Events & Programs
    24 => '',              // Concerts & Movies
    25 => 'fitness.svg',   // Fitness Circuit
    26 => '',              // Disc Golf
    30 => 'golf.svg',      // Golfing
    39 => 'fitness.svg',   // Exercising
    41 => '',              // FootGolf
);

function __construct($id = NULL) {
    parent::__construct($id);
}

/**
 * Get activity types and icons
 *
 * @param $chosen_only: whether to filter by our sub-set of chosen activities.
 */
function getActivityTypesAndIcons($chosen_only=FALSE) {
    $activity_types = new ActivityType();
    $activity_types->order_by('pagetitle')->get();

    $output = array();

    foreach($activity_types as $activity) {
        if ($chosen_only && !in_array($activity->eventactivitytypeid, $activity->chosen_activities)) {
            continue;
        }
        $record = array(
            'title' => $activity->pagetitle,
            'icon' => $activity_types->getActivityIconByID($activity->eventactivitytypeid)
        );
        $output[$activity->eventactivitytypeid] = $record;
    }

    return $output;
}

/**
 * Get only the Activities that have Attractions that feature them, along with icons.
 *
 * @param $chosen_only: whether to filter by our sub-set of chosen activities.
 */
function getActivitiesWithAttractions($chosen_only=FALSE) {
    $sql = "
        SELECT DISTINCT eventactivitytypeid, pagetitle
        FROM (
            SELECT regexp_split_to_table(activities, '\|')
            AS activities
            FROM view_cmp_gisattractions) attract
        LEFT JOIN view_cmp_gisactivitytype activity
            ON (attract.activities = activity.eventactivitytypeid::text)
        ORDER BY pagetitle;
    ";

    $activity_types = new ActivityType();
    $results = $activity_types->db->query($sql)->result();

    $output = array();

    foreach($results as $activity) {
        if ($chosen_only && !in_array($activity->eventactivitytypeid, $activity_types->chosen_activities)) {
            continue;
        }
        $record = array(
            'title' => $activity->pagetitle,
            'icon' => $activity_types->getActivityIconByID($activity->eventactivitytypeid)
        );
        $output[$activity->eventactivitytypeid] = $record;
    }

    return $output;
}

/**
 * Get activity icon by ID
 */
function getActivityIconByID($id) {
    if (!empty($this->activity_type_icons_by_id[$id])) {
        return $this->activity_type_icons_by_id[$id];
    }
}

/**
 * Get activities by ID
 *
 * @param $ids: Activity ID or array of same.
 */
function getActivitiesByID($ids) {
    if (!is_array($ids)) {
        $ids = array($ids);
    }

    $this
        ->where_in('eventactivitytypeid', $ids)
        ->get();

    // Populate the "icon" property of each of our activities.
    foreach ($this as $activity) {
        $activity->icon = $this->getActivityIconByID($activity->eventactivitytypeid);
    }
}



}

/**

Schema creation
(@TODO: move this into migration routines.)
---------------

CREATE TABLE IF NOT EXISTS view_cmp_gisactivitytype (
  eventactivitytypeid integer,
  icon varchar(255),
  phone varchar(255),
  pagetitle varchar(255),
  pagedescription varchar(2000),
  pagethumbnail varchar(255),
  PRIMARY KEY(eventactivitytypeid)
);
GRANT ALL PRIVILEGES ON TABLE view_cmp_gisactivitytype TO trails;

*/