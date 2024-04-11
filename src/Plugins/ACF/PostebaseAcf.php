<?php
namespace Postebase\Plugins\ACF;

class PostebaseAcf
{
  /**
   * Retrieves all ACF fields associated with a specified post type.
   *
   * This function fetches all field groups associated with a given post type
   * and then retrieves all fields within each group, returning a comprehensive
   * list of fields and their configurations.
   *
   * @param string $post_type The slug of the post type for which to retrieve ACF fields.
   * @return array An array of ACF field configurations associated with the specified post type.
   */
  static function get_acf_fields_by_post_type($post_type, $post_id)
  {
    $field_groups = acf_get_field_groups(["post_type" => $post_type]);
    $groups_with_fields = [];

    foreach ($field_groups as $group) {
      // Initialize an array (or object) for the current group
      $group_with_fields = [
        "name" => $group["title"], // Assuming 'title' holds the name of the group
        "fields" => [],
      ];

      $fields = acf_get_fields($group["key"]);
      if ($fields) {
        foreach ($fields as $field) {
          // Add each field's settings to the current group
          $field["value"] = get_field($field["ID"], $post_id, false);
          $group_with_fields["fields"][] = $field;
        }
      }

      // Add the current group (with its fields) to the list of groups
      $groups_with_fields[] = $group_with_fields;
    }

    return $groups_with_fields;
  }
}
