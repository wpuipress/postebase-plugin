<?php
namespace Postebase\Plugins\Yoast;

class PostebaseYoast
{
  /**
   * Intercepts the REST API request to save custom field data for a post.
   *
   * This function checks for the presence of a specific custom field ('my_custom_field')
   * in the REST API request when a post is being created or updated. If the custom field
   * is present, its value is sanitized and saved to the post's meta data. The function
   * ensures that any custom field data sent with a REST API request is properly handled
   * and stored with the post, allowing for extended functionality via the REST API.
   *
   * @param WP_Post $post    The post object that is being inserted or updated.
   * @param WP_REST_Request $request The request object from the REST API call, containing any custom data.
   *
   * @return WP_Post The modified post object, unchanged by this function but required for filter compatibility.
   */
  static function save_yoast_custom_fields($post, $request)
  {
    // Only do this if authenticated and can edit
    if (!is_user_logged_in() || !current_user_can("edit_posts")) {
      return $post;
    }

    // Nom meta request so bail
    if (!$request->get_param("meta")) {
      return $post;
    }

    $meta = $request->get_param("meta");

    // Not array so bail
    if (!is_array($meta)) {
      return;
    }

    // If title exists
    if (isset($meta["_yoast_wpseo_title"])) {
      $cleaned = sanitize_text_field($meta["_yoast_wpseo_title"]);
      update_post_meta($post->ID, "_yoast_wpseo_title", $cleaned);
    }

    // If description exists
    if (isset($meta["_yoast_wpseo_metadesc"])) {
      $cleaned = sanitize_text_field($meta["_yoast_wpseo_metadesc"]);
      update_post_meta($post->ID, "_yoast_wpseo_metadesc", $cleaned);
    }

    return $post;
  }
}
