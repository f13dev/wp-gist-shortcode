<?php
/*
Plugin Name: F13 Gist Shortcode
Plugin URI: http://f13dev.com/wordpress-plugin-gist-shortcode
Description: Embed information about a GitHub Gist into a blog post or page using shortcode.
Version: 1.0
Author: Jim Valentine - f13dev
Author URI: http://f13dev.com
Text Domain: f13-gist-shortcode
License: GPLv3
*/

/*
Copyright 2016 James Valentine - f13dev (jv@f13dev.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Register the shortcode
add_shortcode( 'embedgist', 'f13_gist_shortcode');
// Register the CSS
add_action( 'wp_enqueue_scripts', 'f13_gist_shortcode_stylesheet');
// Register the admin page
add_action('admin_menu', 'f13_gs_create_menu');

function f13_gist_shortcode( $atts, $content = null )
{
    // Get the attributes
    extract( shortcode_atts ( array (
        'gist' => '', // Get the gist ID
    ), $atts ));

    // Check if the gist attribute has been received
    if ($gist != '')
    {
        // The gist attribute is present, attempt to get the
        // api response.
        $data = f13_get_gist_data($gist);

        // Check to see if an error message has been returned,
        if (array_key_exists('message', $data))
        {
            // Alert the user of the error
            $response = 'The Gist ID: \'' . $gist . '\' returned an error.<br />
                Message: ' . $data['message'] . '<br />';
        }
        else
        {
            // A response has been generated and the response does not appear
            // to contain an error message.

            // Return the response of formatting the data
            $response = f13_format_gist_data($data);
        }
    }
    else
    {
        // The gist attribute has not been received
        $response = 'The gist attribute is required<br />
            E.g. [embedgist gist="a gist ID"]';
    }
    // Return the response
    return $response;
}

function f13_format_gist_data($gistData)
{
    // Create a new variable to hold the response
    $response = '';

    // Open a container div
    $response .= '<div class="f13-gist-container">';

        // Testing code

        // add username of creator
        $response .= '<div class="f13-gist-header">
            Created by: <a href="https://github.com/' . $gistData['owner']['login'] . '">' . $gistData['owner']['login'] . '</a>
        </div>';

        // Add created at/ updated
        $response .= '<div class="f13-gist-created">
            Created at: ' . f13_get_git_date($gistData['created_at']) . '
        </div>';

        foreach ($gistData['files'] as &$eachFile)
        {
            // Add the filename and the size of the file
            $response .= $eachFile['filename'] . ' (' . round($eachFile['size'] / 1024, 2) . 'kb) <a href="' . $eachFile['raw_url'] . '" download>Download file</a><br />';
            $response .= '<pre class="prettyprint lang-' . strtolower($eachFile['language']) . '" style="border: 1px solid black; margin: 10px; padding: 10px; max-height: 200px; overflow: scroll">';
                $response .= nl2br(htmlentities($eachFile['content']));
            $response .= '</pre>';
        }

    // Close the container div
    $response .= '</div>';
    return $response;
}

function f13_get_git_date($aDate)
{
    // Explode the date
    $aDate = explode('-', $aDate);

    // Set the year
    $aYear = $aDate[0];

    // Set the month
    $aMonth = $aDate[1];

    // Set the day
    $aDay = $aDate[2][0] . $aDate[2][1];

    return $aDay . ' ' . $aMonth . ' ' . $aYear;

}

function f13_get_gist_data($aGist)
{
    // Start curl
    $curl = curl_init();

    // Generate the URL
    $url = 'https://api.github.com/gists/' . $aGist;

    // Set curl options
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPGET, true);

    // Get the token from the admin panel
    $token = esc_attr( get_option('f13gs_token'));

    // Check if a token has been entered.
    if (preg_replace('/\s+/', '', $token) != '' || $token != null)
    {
        // If a token is set attempt to send it in the header
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: token ' . $token
        ));
    }
    else
    {
        // If no token is set, send the header as unauthenticated,
        // some features may not work and a lower rate limit applies.
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
    }

    // Set the user agent
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    // Set curl to return the response, rather than print it
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Get the results
    $result = curl_exec($curl);

    // Close the curl session
    curl_close($curl);

    // Decode the results
    $result = json_decode($result, true);

    // Return the results
    return $result;
}

function f13_gist_shortcode_stylesheet()
{
    wp_register_style( 'f13gist-style', plugins_url('wp-gist-shortcode.css', __FILE__));
    wp_enqueue_style( 'f13gist-style' );

    // Also register prettyprint javascript files
    wp_enqueue_script('PrettyPrint', plugins_url('prettyprint/run_prettify.js', __FILE__));
}

function f13_gs_create_menu()
{
    // Create the sub-level menu
    add_options_page('F13Devs Gist Shortcode Settings', 'F13 Gist Shortcode', 'administrator', 'f13-gist-shortcode', 'f13_gs_settings_page');
    // Retister the Settings
    add_action( 'admin_init', 'f13_gs_settings');
}

function f13_gs_settings()
{
    // Register settings for token and timeout
    register_setting( 'f13-gs-settings-group', 'f13gs_token');
    register_setting( 'f13-gs-settings-group', 'f13gs_timeout');
}

function f13_gs_settings_page()
{
    ?>
        <div class="wrap">
            <h2>F13 Gist Shortcode Settings</h2>
            <p>
                This plugin will work with or without a GitHub API token
            </p>
            <p>
                To obtain an API token:
                <ol>
                    <li>
                        Instructions.
                    </li>
                </ol>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( 'f13-gs-settings-group' ); ?>
                <?php do_settings_sections( 'f13-gs-settings-group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            GitHub API Key
                        </th>
                        <td>
                            <input type="password" name="f13gs_token" value="<?php echo esc_attr( get_option( 'f13bs_token' ) ); ?>" style="width: 50%;"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            Cache timeout (minutes)
                        </th>
                        <td>
                            <input type="number" name="f13gs_timeout" value="<?php echo esc_attr( get_option( 'f13bs_timeout' ) ); ?>" style="width: 75px;"/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php
}
