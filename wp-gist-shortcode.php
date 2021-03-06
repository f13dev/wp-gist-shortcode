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
add_shortcode( 'gist', 'f13_gist_shortcode');
// Register the CSS
add_action( 'wp_enqueue_scripts', 'f13_gist_shortcode_stylesheet');
// Register the admin page
add_action('admin_menu', 'f13_gs_create_menu');

function f13_gist_shortcode( $atts, $content = null )
{
    // Get the attributes
    extract( shortcode_atts ( array (
        'gid' => '', // Get the gist ID
    ), $atts ));

    // Check if the gist attribute has been received
    if ($gid != '')
    {
        // The gist attribute is present, attempt to get the
        // api response.
        $data = f13_get_gist_data($gid);

        // Check to see if an error message has been returned,
        if (array_key_exists('message', $data))
        {
            // Alert the user of the error
            $response = 'The Gist ID: \'' . $gid . '\' returned an error.<br />
                Message: ' . $data['message'] . '<br />';
        }
        else
        {
            // A response has been generated and the response does not appear
            // to contain an error message. From now on data will be cached.

            // Set the cache name and prefix
            $cache = get_transient('f13gist' . md5(serialize($atts)));

            if ($cache)
            {
                // If the cache exists, return it rather than re-creating it
                return $cache;
            }
            else
            {
                // If a cache does not exist, create the shortcode content

                // Generate the formatted data and save it as the response.
                $response = f13_format_gist_data($data);

                // Get the cache timeout and convert it from minutes to seconds
                $timeout = esc_attr( get_option('f13gs_timeout')) * 60;

                // If the timeout is set to zero seconds, change it to 1 second,
                // otherwise the cache will never timeout.
                if ($timeout == 0 || !is_numeric($timeout))
                {
                    $timeout = 1;
                }

                // Set the transient cache using the response and timeout value
                set_transient('f13gist' . md5(serialize($atts)), $response, $timeout);
            }
        }
    }
    else
    {
        // The gist attribute has not been received
        $response = 'The gist attribute is required<br />
            E.g. [gist gid="a gist ID"]';
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

        // add username of creator
        $response .= '<div class="f13-gist-header">
            <svg aria-hidden="true" version="1.1" viewBox="0 0 16 16"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0 0 16 8c0-4.42-3.58-8-8-8z"></path></svg>
            Gist created by: <a href="https://github.com/' . $gistData['owner']['login'] . '">' . $gistData['owner']['login'] . '</a>
        </div>';

        // Add created at/ updated
        $response .= '<div class="f13-gist-created">
            Created at: ' . f13_get_git_date($gistData['created_at']) . '
            | Last edited: ' . f13_get_git_date($gistData['updated_at']) . '
            | <a href="https://gist.github.com/' . $gistData['owner']['login'] . '/' . $gistData['id'] . '">View on GitHub</a>
        </div>';

        // Create a description div
        $response .= '<div class="f13-gist-description">';

            // Add a span to contain the title
            $response .= '<span>Description:</span> ';

            // Check if a description is set
            if ($gistData['description'] != '')
            {
                // Add the description
                $response .= htmlentities($gistData['description']);
            }
            else
            {
                // If no description is set, respond n/a
                $response .= 'N/A';
            }

        // Close the description div
        $response .= '</div>';


        // Add a horizontal rule to seperate the header data and file data
        $response .= '<hr />';

        // Add a div for the files head
        $response .= '<div class="f13-gist-files-head">';

            // Add the header text and number of files
            $response .= '<span>Files</span> (' . count($gistData['files']) . ')';

        // Close the div for the files head
        $response .= '</div>';

        foreach ($gistData['files'] as &$eachFile)
        {
            // Add the Gist icon
            $response .= '<svg aria-hidden="true" version="1.1" viewBox="0 0 12 16"><path d="M7.5 5L10 7.5 7.5 10l-.75-.75L8.5 7.5 6.75 5.75 7.5 5zm-3 0L2 7.5 4.5 10l.75-.75L3.5 7.5l1.75-1.75L4.5 5zM0 13V2c0-.55.45-1 1-1h10c.55 0 1 .45 1 1v11c0 .55-.45 1-1 1H1c-.55 0-1-.45-1-1zm1 0h10V2H1v11z"></path></svg>';

            // Add the filename and size
            $response .= $eachFile['filename'] . ' (' . round($eachFile['size'] / 1024, 2) . 'kb) <a href="' . $eachFile['raw_url'] . '" download>Download file</a><br />';

            // Create a prettyprint pre element
            $response .= '<pre class="prettyprint lang-' . strtolower($eachFile['language']) . '" style="border: 1px solid black; margin: 10px; padding: 10px; max-height: 200px; overflow: scroll">';

                // Add the file contents using nl2br to create new lines
                // and htmlentities to convert symbols such as < to &lt; etc...
                $response .= nl2br(htmlentities($eachFile['content']));

            // Close the prettyprint pre element
            $response .= '</pre>';
        }

        // Add a horizontal rule to end the files section
        $response .= '<hr />';

        // Add comment count
        $response .= 'Comments: <a href="https://gist.github.com/' . $gistData['owner']['login'] . '/' . $gistData['id'] . '#comments">' . $gistData['comments'] . '</a>';

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
    $aMonth = DateTime::createFromFormat('!m', $aDate[1])->format('F');

    // Set the day
    $aDay = $aDate[2][0] . $aDate[2][1];

    // Return the re-formatted date
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
                Welcome to the settings page for GitHub Gist Shortcode.
            </p>
            <p>
                This plugin can be used without an API token, although it is recommended to use one as the number of API calls is quite restrictive without one.
            </p>
            <p>
                To obtain a GitHub API token:
                <ol>
                    <li>
                        Log-in to your GitHub account.
                    </li>
                    <li>
                        Visit <a href="https://github.com/settings/tokens">https://github.com/settings/tokens</a>.
                    </li>
                    <li>
                        Click the 'Generate new token' button at the top of the page/
                    </li>
                    <li>
                        Re-enter your GitHub password for security.
                    </li>
                    <li>
                        Enter a description, such as 'my wordpress site'.
                    </li>
                    <li>
                        Click the 'Generate token' button at the bottom of the page, no other setting changes are required.
                    </li>
                    <li>
                        Copy and paste your new API token. Please note, your access token will only be visible once.
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
                            <input type="password" name="f13gs_token" value="<?php echo esc_attr( get_option( 'f13gs_token' ) ); ?>" style="width: 50%;"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            Cache timeout (minutes)
                        </th>
                        <td>
                            <input type="number" name="f13gs_timeout" value="<?php echo esc_attr( get_option( 'f13gs_timeout' ) ); ?>" style="width: 75px;"/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h3>Shortcode example</h3>
            <p>
                If you wish to display a widget showing details of a gist at: https://gist.github.com/f13dev/fc53666cfbde382ca6a5ae1c519dc65a use the following shortcode, obtaining the 'gid' from the end of the gist.github.com URL:
            </p>
            <p>
                [gist gid="fc53666cfbde382ca6a5ae1c519dc65a"]
            </p>
        </div>
    <?php
}
