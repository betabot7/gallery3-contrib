<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2009 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

class Admin_TagsMap_Controller extends Admin_Controller {
  public function index() {
    // Generate a new admin page.
    $view = new Admin_View("admin.html");
    $view->content = new View("admin_tagsmap.html");

    // Generate a form for Google Maps Settings.
    $view->content->googlemaps_form = $this->_get_googlemaps_form();
    
    // Generate a list of tags to display.
    $query = ORM::factory("tag");
    $view->content->tags = $query->orderby("name", "ASC")->find_all();

    // Display the page.
    print $view;
  }

  public function edit_gps($tag_id) {
    // Generate a new admin page to edit gps data for the tag specified by $tag_id.
    $view = new Admin_View("admin.html");
    $view->content = new View("admin_tagsmap_edit.html");
    $view->content->tagsmapedit_form = $this->_get_tagsgpsedit_form($tag_id);
    print $view;
  }

  public function confirm_delete_gps($tag_id) {
    // Make sure the user meant to hit the delete button.
    $view = new Admin_View("admin.html");
    $view->content = new View("admin_tagsmap_delete.html");
    $view->content->tag_id = $tag_id;
    print $view;
  }

  public function delete_gps($tag_id) {
    // Delete the GSP data associated with a tag.

    // Delete the record.
    ORM::factory("tags_gps")
      ->where("tag_id", $tag_id)
      ->delete_all();

    // Redirect back to the main screen and display a "success" message.
    message::success(t("Your Settings Have Been Saved."));
    url::redirect("admin/tagsmap");
  }

  private function _get_tagsgpsedit_form($tag_id) {
    // Make a new form for editing GPS data associated with a tag ($tag_id).
    $form = new Forge("admin/tagsmap/savegps", "", "post",
                      array("id" => "gTagsMapAdminForm"));

    // Add a few input boxes for GPS and Description
    $tagsgps_group = $form->group("TagsMapGPS");
    $tagsgps_group->hidden("tag_id")->value($tag_id);

    // Check and see if this ID already has GPS data, then create
    //  input boxes to either update it or enter in new information.
    $existingGPS = ORM::factory("tags_gps")
      ->where("tag_id", $tag_id)
      ->find_all();
    if (count($existingGPS) == 0) {
      $tagsgps_group->input("gps_latitude")->label(t("Latitude"))->value();
      $tagsgps_group->input("gps_longitude")->label(t("Longitude"))->value();
      $tagsgps_group->textarea("gps_description")->label(t("Description"))->value();
    } else {
      $tagsgps_group->input("gps_latitude")->label(t("Latitude"))->value($existingGPS[0]->latitude);
      $tagsgps_group->input("gps_longitude")->label(t("Longitude"))->value($existingGPS[0]->longitude);
      $tagsgps_group->textarea("gps_description")->label(t("Description"))->value($existingGPS[0]->description);
    }

    // Add a save button to the form.
    $tagsgps_group->submit("SaveGPS")->value(t("Save"));

    // Return the newly generated form.
    return $form;
  }

  public function savegps() {
    // Save the GPS coordinates to the database.

    // Prevent Cross Site Request Forgery
    access::verify_csrf();

    // Figure out the values of the text boxes
    $str_tagid = Input::instance()->post("tag_id");
    $str_latitude = Input::instance()->post("gps_latitude");
    $str_longitude = Input::instance()->post("gps_longitude");
    $str_description = Input::instance()->post("gps_description");

    // Save to database.    
    // Check and see if this ID already has GPS data,
    //   Update it if it does, create a new record if it doesn't.
    $existingGPS = ORM::factory("tags_gps")
      ->where("tag_id", $str_tagid)
      ->find_all();
    if (count($existingGPS) == 0) {
      $newgps = ORM::factory("tags_gps");
      $newgps->tag_id = $str_tagid;
      $newgps->latitude = $str_latitude;
      $newgps->longitude = $str_longitude;
      $newgps->description = $str_description;
      $newgps->save();
    } else {
      $updatedGPS = ORM::factory("tags_gps", $existingGPS[0]->id);
      $updatedGPS->tag_id = $str_tagid;
      $updatedGPS->latitude = $str_latitude;
      $updatedGPS->longitude = $str_longitude;
      $updatedGPS->description = $str_description;
      $updatedGPS->save();
    }
    
    // Redirect back to the main screen and display a "success" message.
    message::success(t("Your Settings Have Been Saved."));
    url::redirect("admin/tagsmap");
  }

  private function _get_googlemaps_form() {
    // Make a new form for inputing information associated with google maps.
    $form = new Forge("admin/tagsmap/savemapprefs", "", "post",
                      array("id" => "gTagsMapAdminForm"));

    // Input box for the Maps API Key
    $googlemap_group = $form->group("GoogleMapsKey");
    $googlemap_group->input("google_api_key")
                 ->label(t("Google Maps API Key"))
                 ->value(module::get_var("tagsmap", "googlemap_api_key"));

    // Input boxes for the Maps starting location map type and zoom.
    $startingmap_group = $form->group("GoogleMapsPos");
    $startingmap_group->input("google_starting_latitude")
                 ->label(t("Starting Latitude"))
                 ->value(module::get_var("tagsmap", "googlemap_latitude"));
    $startingmap_group->input("google_starting_longitude")
                 ->label(t("Starting Longitude"))
                 ->value(module::get_var("tagsmap", "googlemap_longitude"));
    $startingmap_group->input("google_default_zoom")
                 ->label(t("Default Zoom Level"))
                 ->value(module::get_var("tagsmap", "googlemap_zoom"));
    $startingmap_group->input("google_default_type")
                 ->label(t("Default Map Type") . " (G_NORMAL_MAP, G_SATELLITE_MAP, G_HYBRID_MAP, G_PHYSICAL_MAP, G_SATELLITE_3D_MAP)")
                 ->value(module::get_var("tagsmap", "googlemap_type"));
                 
    // Add a save button to the form.
    $form->submit("SaveSettings")->value(t("Save"));

    // Return the newly generated form.
    return $form;
  }
  
  public function savemapprefs() {
    // Save information associated with Google Maps to the database.

    // Prevent Cross Site Request Forgery
    access::verify_csrf();

    // Figure out the values of the text boxes
    $str_googlekey = Input::instance()->post("google_api_key");
    $str_googlelatitude = Input::instance()->post("google_starting_latitude");
    $str_googlelongitude = Input::instance()->post("google_starting_longitude");
    $str_googlezoom = Input::instance()->post("google_default_zoom");
    $str_googlemaptype = Input::instance()->post("google_default_type");
    
    // Save Settings.
    module::set_var("tagsmap", "googlemap_api_key", $str_googlekey);
    module::set_var("tagsmap", "googlemap_latitude", $str_googlelatitude);
    module::set_var("tagsmap", "googlemap_longitude", $str_googlelongitude);
    module::set_var("tagsmap", "googlemap_zoom", $str_googlezoom);
    module::set_var("tagsmap", "googlemap_type", $str_googlemaptype);

    // Display a success message and redirect back to the TagsMap admin page.
    message::success(t("Your Settings Have Been Saved."));
    url::redirect("admin/tagsmap");
  }
}