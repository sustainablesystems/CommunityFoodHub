<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* NOTE: This config file is included from apiClient.php. */

global $apiConfig;

$apiConfig = array(

    /************************ CONFIGURE THESE SETTINGS FOR YOUR WEBSITE ***********************/

    /*
     * The following values MUST match those in the Google APIs Console for the account
     * where the Google Calendar is hosted, e.g. "fguangels@gmail.com".
     * Log in to the account, go to https://code.google.com/apis/console, and click "API Access".
     *
     * NOTE: To gain access to the volunteer rota, users will be prompted to enter
     * the email address and password of the Gmail account hosting the Calendar
     * (here: fguangels@gmail.com).
     */

    
    // API Key from the Google APIs Console
    'developer_key' => 'TODO',

    // OAuth2 settings from the Google APIs Console
    'oauth2_client_id' => 'TODO.apps.googleusercontent.com',
    'oauth2_client_secret' => 'TODO',
    
    // Volunteer rota page that the user is taken to after access is granted
    'oauth2_redirect_uri' => 'http://localhost/foodcoop/shop/members/volunteer_rota.php',

    // Site name to show in Google's OAuth2 authentication screen
    'site_name' => 'fgu.ttkingston.org/shop',

    /******************************************************************************************/

    'use_objects' => true,

    // Which Authentication, Storage and HTTP IO classes to use.
    'authClass'    => 'apiOAuth2',
    'ioClass'      => 'apiCurlIO',
    'cacheClass'   => 'apiFileCache',

    // Don't change these unless you're working against a special development or testing environment.
    'basePath' => 'https://www.googleapis.com',
    
    'ioFileCache_directory'  =>
        (function_exists('sys_get_temp_dir') ?
            sys_get_temp_dir() . '/apiClient' :
        '/tmp/apiClient'),

    // Definition of service specific values like scopes, OAuth token URLs, etc
    // calendar.readonly is another option
    'services' => array(
        'calendar' => array('scope' => 'https://www.googleapis.com/auth/calendar'),
    )
);
