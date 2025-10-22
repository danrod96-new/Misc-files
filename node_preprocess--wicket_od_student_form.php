<?php

use Drupal\Core\Site\Settings;
use Wicket\Entities\Addresses;
use Wicket\Entities\Emails;
use Wicket\Entities\People;
use Wicket\Entities\Phones;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Render\Markup;


/**
 * Implements hook_preprocess_HOOK() for HTML document templates.
 *
 * @param array $variables
 *
 * @throws \JsonException
 */
function cao_theme_preprocess_node__wicket_od_student_form(array &$variables): void
{
    $language = Drupal::languageManager()->getCurrentLanguage()->getId();

    $variables['googleRecaptchaSiteKey'] = Settings::get('google_recaptcha_site') ;

    $variables['language'] = $language;
    $variables['#cache']['contexts'] = ['url.query_args'];

    // Clear cache when nodes change.
    $variables['#cache']['tags'][] = 'node_list';
    $variables['#cache']['max-age'] = 0;
    $variables['get'] = $_GET;
    $variables['post'] = $_POST;

    $client = wicket_api_client();

    $errors = [];

    if (isset($_POST['create_profile'])) {
        /**------------------------------------------------------------------
         * Process new account data
         * ------------------------------------------------------------------*/
        $prefix = $_POST['honorific_prefix'] ?? '';
        $first_name = trim($_POST['given_name']) ?? '';
        $middle_name = trim($_POST['alternate_name']) ?? '';
        $last_name = trim($_POST['family_name']) ?? '';
        $suffix = $_POST['honorific_suffix'] ?? '';
        $email_address = trim($_POST['email_address']) ?? '';
        $email_confirmation = trim($_POST['confirm_email_address']) ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['confirm_password'] ?? '';
        $phone = trim($_POST['phone']) ?? '';
        $mobile_phone = trim($_POST['mobile_phone']) ?? '';
        $country = $_POST['country'] ?? '';
        $address = trim($_POST['address1']) ?? '';
        $city = trim($_POST['city']) ?? '';
        $province = $_POST['province'] ?? '';
        $zip_code = trim($_POST['zip_code']) ?? '';
        $school = $_POST['school'] ?? '';
        $graduationYear = trim($_POST['graduationYear']) ?? '';
        $sendCJO = $_POST['sendCJO'] ?? '';
        $primary_language = $_POST['language'] ?? '';
        $languages_spoken = $_POST['languages_spoken'] ?? '';

        if ($first_name === '') {
            $first_name_blank = new stdClass();
            $first_name_blank->meta = (object) [
                'field' => 'user.given_name'
            ];
            $first_name_blank->title = t('Please provide your first name');

            $errors[] = $first_name_blank;
        }

        if ($last_name === '') {
            $last_name_blank = new stdClass();
            $last_name_blank->meta = (object) [
                'field' => 'user.family_name'
            ];
            $last_name_blank->title = t('Please provide your last name');

            $errors[] = $last_name_blank;
        }

        if ($email_address === '') {
            $email_blank = new stdClass();
            $email_blank->meta = (object) [
                'field' => 'emails.address'
            ];
            $email_blank->title = t('Please provide a valid email address');

            $errors[] = $email_blank;
        }

        if ($email_address !== '' && ($email_confirmation !== $email_address)) {
            $email_confirm = new stdClass();
            $email_confirm->meta = (object) [
                'field' => 'emails.confirm_address'
            ];
            $email_confirm->title = t(' - Emails do not match');

            $errors[] = $email_confirm;
        }

        if ($password === '') {
            $pass_blank = new stdClass();
            $pass_blank->meta = (object) [
                'field' => 'user.password'
            ];
            $pass_blank->title = t('Please provide a secure password');

            $errors[] = $pass_blank;
        }

        if ($password_confirmation === '') {
            $confirm_pass_blank = new stdClass();
            $confirm_pass_blank->meta = (object) [
                'field' => 'user.password_confirmation'
            ];
            $confirm_pass_blank->title = t('Please re-enter your secure password');

            $errors[] = $confirm_pass_blank;
        }

        if ($password_confirmation !== $password) {
            $pass_blank = new stdClass();
            $pass_blank->meta = (object) [
                'field' => 'user.password'
            ];
            $pass_blank->title = t(' - Passwords do not match');

            $errors[] = $pass_blank;
        }

        if ($address === '') {
            $address_blank = new stdClass();
            $address_blank->meta = (object) [
                'field' => 'user.address'
            ];
            $address_blank->title = t('Please provide your street address');

            $errors[] = $address_blank;
        }

        if ($city === '') {
            $city_blank = new stdClass();
            $city_blank->meta = (object) [
                'field' => 'user.city'
            ];
            $city_blank->title = t('Please provide the city you reside in');

            $errors[] = $city_blank;
        }

        if ($province === '') {
            $province_blank = new stdClass();
            $province_blank->meta = (object) [
                'field' => 'user.province'
            ];
            $province_blank->title = t('Please provide the province you reside in');

            $errors[] = $province_blank;
        }

        if ($country === '') {
            $country_blank = new stdClass();
            $country_blank->meta = (object) [
                'field' => 'user.country'
            ];
            $country_blank->title = t('Please provide the country you reside in');

            $errors[] = $country_blank;
        }

        if ($zip_code === '') {
            $zip_code_blank = new stdClass();
            $zip_code_blank->meta = (object) [
                'field' => 'user.zip_code'
            ];
            $zip_code_blank->title = t('Please provide your address postal/zip code');

            $errors[] = $zip_code_blank;
        }

        if ($school === '') {
            $school_blank = new stdClass();
            $school_blank->meta = (object) [
                'field' => 'user.school'
            ];
            $school_blank->title = t('Please provide your educational institution');

            $errors[] = $school_blank;
        }

        if ($graduationYear === '') {
            $graduationYear_blank = new stdClass();
            $graduationYear_blank->meta = (object) [
                'field' => 'user.graduationYear'
            ];
            $graduationYear_blank->title = t('Please provide your expected graduation year');

            $errors[] = $graduationYear_blank;
        }

        $_SESSION['wicket_create_account_form_errors'] = $errors;

        // Don't send anything if errors.
        if (empty($errors)) {
            $args = [
                'query' => [
                    'filter' => [
                        'alternate_name_en_eq' => 'CAO'
                    ],
                    'page' => [
                        'number' => 1,
                        'size' => 1
                    ]
                ]
            ];
            $org = $client->get('organizations', $args);

            /**------------------------------------------------------------------
             * Build additional info
             * ------------------------------------------------------------------*/
            $education_schema = 'urn:uuid:334427e1-cb83-4f83-beb4-688d896c8d1c';
            $communication_preferences_schema = 'urn:uuid:28f8a274-6be7-4e7f-a581-ae2b4c798986';

            $data_fields = [];

            if (! empty($school)) {
                $data_fields[$education_schema]['$schema'] = $education_schema;
                $data_fields[$education_schema]['value']['university'] = $school;
            }

            if (! empty($graduationYear)) {
                $data_fields[$education_schema]['$schema'] = $education_schema;
                $data_fields[$education_schema]['value']
                    ['graduationYear'] = (int) $graduationYear;
            }

            if (! empty($sendCJO)) {
                $data_fields[$communication_preferences_schema]
                    ['$schema'] = $communication_preferences_schema;
                $data_fields[$communication_preferences_schema]['value']
                    ['sendCJO'] = $sendCJO;
            }

            if (isset($_POST['preferences'])) {
                $data_fields[$communication_preferences_schema]
                    ['$schema'] = $communication_preferences_schema;
                $data_fields[$communication_preferences_schema]['value']
                    ['preferences'] = $_POST['preferences'];
            }

            $user = [
                'given_name' => $first_name,
                'alternate_name' => $last_name,
                'honorific_prefix' => $prefix,
                'honorific_suffix' => $suffix,
                'family_name' => $last_name,
                'language' => $primary_language,
                'languages_spoken' => $languages_spoken,
                'data' => [
                    'communications' => [
                        'email' => true,
                        'sublists' => [
                            'one' => true
                        ]
                    ]
                ],
                'user' => [
                    'password' => $password,
                    'password_confirmation' => $password_confirmation
                ],
                'data_fields' => $data_fields
            ];

            /**------------------------------------------------------------------
             * Create new person
             * ------------------------------------------------------------------*/
            $person = new People($user);
            $email = new Emails([
                'address' => $email_address,
                'primary' => true,
                'type' => 'personal'
            ]);

            $person->attach($email);

            try {
                $new_person = $client->people->create($person, (object) $org['data'][0]);
            } catch (Exception $e) {
                $_SESSION['wicket_create_account_form_errors'] = json_decode(
                    $e->getResponse()->getBody(),
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                )->errors;
            }

            /**------------------------------------------------------------------
             * Assign address to new person
             * ------------------------------------------------------------------*/
            if (! empty($new_person['data']['id'])) {
                $address = new Addresses([
                    'state_name' => $province,
                    'country_code' => $country,
                    'zip_code' => $zip_code,
                    'address1' => $address,
                    'city' => $city,
                    'primary' => true,
                    'type' => 'home'
                ]);

                $new_person = wicket_get_person_by_id($new_person['data']['id']);

                try {
                    $client->people->create($address, $new_person);
                } catch (Exception $e) {
                    $_SESSION['wicket_create_account_form_errors'] = json_decode(
                        $e->getResponse()->getBody(),
                        false,
                        512,
                        JSON_THROW_ON_ERROR
                    )->errors;
                }
            }

            /**------------------------------------------------------------------
             * Assign phone to new person
             * ------------------------------------------------------------------*/
            if (isset($new_person) && $phone) {
                $phone = new Phones([
                    'number' => $phone,
                    'type' => 'home'
                ]);

                try {
                    $client->people->create($phone, $new_person);
                } catch (Exception $e) {
                    $_SESSION['wicket_create_account_form_errors'] = json_decode(
                        $e->getResponse()->getBody(),
                        false,
                        512,
                        JSON_THROW_ON_ERROR
                    )->errors;
                }
            }

            /**------------------------------------------------------------------
             * Assign mobile phone to new person
             * ------------------------------------------------------------------*/
            if (isset($new_person) && $mobile_phone) {
                $mobile_phone = new Phones([
                    'number' => $mobile_phone,
                    'type' => 'mobile'
                ]);

                try {
                    $client->people->create($mobile_phone, $new_person);
                } catch (Exception $e) {
                    $_SESSION['wicket_create_account_form_errors'] = json_decode(
                        $e->getResponse()->getBody(),
                        false,
                        512,
                        JSON_THROW_ON_ERROR
                    )->errors;
                }
            }

            /**------------------------------------------------------------------
             * Assign 'user' role so they do NOT get the sign-up confirmation email.
             * ------------------------------------------------------------------*/

            // Get all roles.

            /* $user_roles = [];

            $roles = $client->get('roles');

            foreach ($roles['data'] as $role) {
                if ($role['attributes']['slug'] === 'user') {
                    $user_roles[] = $role;
                }

                if ($role['attributes']['slug'] === 'ccoa-student') {
                    $user_roles[] = $role;
                }
            }

            foreach ($user_roles as $user_role) {
                $relationship_payload['data'][] = [
                    'type' => 'roles',
                    'id' => $user_role['id']
                ];
            } 
                
            $user_role = []; */

            /* CAO 10-21-2025: First, add the "user" role to the person */
            /*$roles = $client->get('roles');

            foreach ($roles['data'] as $role) {
                if ($role['attributes']['slug'] === 'user') {
                    $user_role = $role;
                }
            }

            $relationship_payload['data'][] = [
                'type' => 'roles',
                'id' => $user_role['id']
            ];*/

            $relationship_payload = [
              'data' => [
                'type' => 'roles',
                'attributes' => [
                  'name' => 'user',
                ]
              ]
            ];

            // Lookup the user membership to get the uuid.
           try {
                $user_membership = $client->get(
                    'memberships?filter[type_eq]=individual&filter[slug_eq]=user'
                );
            } catch (Exception $e) {}

            if (isset($user_membership['data'][0]['id'])) {
              $relationship_payload['data']['relationships']['resource']['data']['id'] = $user_membership;
              $relationship_payload['data']['relationships']['resource']['data']['type'] = 'organizations';
            }
            
            if (isset($new_person) && $new_person->id) {
                try {
                    $client->post(
                        'people/' . $new_person->id . '/relationships/roles', [
                            'json' => $relationship_payload
                        ]
                    );
                } catch (Exception $e) {}
            } 

            /* CAO 10-21-2025: Don't add the other roles, let the CAO admin to do that */
            /**------------------------------------------------------------------
             * Assign membership to the user
             * ------------------------------------------------------------------*/

            // First lookup the user membership to get the uuid.
           try {
                $user_membership = $client->get(
                    'memberships?filter[type_eq]=individual&filter[slug_eq]=user'
                );
            } catch (Exception $e) {}

            if (isset($user_membership['data'][0]['id'])) {
                // Build payload to assign person to a membership.
                $payload = [
                    'data' => [
                        'type' => 'person_memberships',
                        'attributes' => [
                            'starts_at' => date('c'),
                            "ends_at" => date('c', strtotime('+366 day'))
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => [
                                    'id' => (isset($new_person) && ($new_person->id))
                                        ? $new_person->id : "",
                                    'type' => 'people'
                                ]
                            ],
                            'membership' => [
                                'data' => [
                                    'id' => $user_membership['data'][0]['id'],
                                    'type' => 'memberships'
                                ]
                            ]
                        ]
                    ]
                ];

                try {
                    $client->post('person_memberships', [
                        'json' => $payload
                    ]);
                } catch (Exception $e) {
                    $errors = json_decode(
                        $e->getResponse()->getBody(),
                        false,
                        512,
                        JSON_THROW_ON_ERROR
                    )->errors;
                }
            }

            /** 
             * CAO 09-01-2025: Send an email to info@opto.ca with the new user info 
             * to approve.
             */
            $config = \Drupal::config('wicket.settings');
            $wicked_admin_page = $config->get('wicket_admin_settings_wicket_admin');
            $mailManager = \Drupal::service('plugin.manager.mail');
            $module = 'wicket';
            $key = 'approve_student_membership';
            $to = \Drupal::config('system.site')->get('mail');
            $params['mail_title'] = t("CAO / ACO: Approve Student Membership");
            $params['message'] = new TranslatableMarkup("<div><p>Hello, </p><p>A new request for a student membership approval for the user <strong>@student_email</strong> needs to be verified and approved.</p><p>Please login into the Wicket Platform to review the account: @wicket_panel</p></div><br /> Thank you.", ["@student_email" => $email_address, '@wicket_panel' => $wicked_admin_page]);
            $langcode = \Drupal::currentUser()->getPreferredLangcode();
            $send = true;
            $result = $mailManager->mail($module, $key, $to, $langcode, $params, null, $send);

            /** 
             * CAO 09-01-2025: Send an email to the student that their account is being reviewed
             */
            $mailManager = \Drupal::service('plugin.manager.mail');
            $module = 'wicket';
            $key = 'student_confirmation_review';
            $to = $email_address;
            $params['mail_title'] = t("CAO / ACO: Student Confirmation Review");
            $langcode = \Drupal::currentUser()->getPreferredLangcode();
            $params['message'] = new TranslatableMarkup("<p>La version française suit le texte anglais.</p>
                <p>Welcome to the Canadian Association of Optometrists!<br><br>You are receiving this email because your account is under review by the CAO Staff.</p>
                <p>After verifying your account, your login will be activated and you will receive another confirmation email with a link to login and change your settings and update your personal information</p>
                <br>Should you have any questions, please contact <a href='mailto:info@opto.ca'>info@opto.ca</a> or call 613-235-7924.</p>
                <p></p>
                <p>Please do not reply to this email. Emails sent to this address will not be monitored.</p>
                <hr>
                <p>Bienvenue à l'Association canadienne des optométristes! (ACO)<br><br>Vous recevez cet e-mail parce que votre compte est en cours d'examen par le personnel du ACO.</p>
                <p>Après avoir vérifié votre compte, votre connexion sera activée et vous recevrez un autre e-mail de confirmation avec un lien pour vous connecter et modifier vos paramètres et mettre à jour vos informations personnelles.</p>
                <br>Si vous avez des questions, veuillez contacter <a href='mailto:info@opto.ca'>info@opto.ca</a> ou appeler le 613-235-7924.</p>
                <p></p>
                <p>Ne pas répondre à ce message. Les messages envoyés à cette adresse ne sont pas consultés.</p>");
            $send = true;
            $result = $mailManager->mail($module, $key, $to, $langcode, $params, null, $send);

            /**------------------------------------------------------------------
             * Redirect to a verify page if person was created
             * ------------------------------------------------------------------*/
            if (empty($_SESSION['wicket_create_account_form_errors'])) {
                unset($_SESSION['wicket_create_account_form_errors']);

                $lang_prefix = $language === 'fr' ? '/fr' : "";
                $creation_redirect_path = $lang_prefix . (
                    ($language === 'fr') ? '/verifier-votre-compte' : '/verify-account'
                );

                header('Location: ' . $creation_redirect_path);
                die;
            }
        }
    } else {
        unset($_SESSION['wicket_create_account_form_errors']);
    }

    if (isset($_SESSION['wicket_create_account_form_errors'])
        && ! empty($_SESSION['wicket_create_account_form_errors'])
    ) {
        $variables['wicket_create_account_form_errors'] = $_SESSION
            ['wicket_create_account_form_errors'];
    }

    $variables['country_list'] = wicket_country_list();
    $variables['provinces'] = wicket_custom_ccua_get_provinces();
    $variables['schools'] = wicket_get_schools();

    $languages_arr = [];
    if (($languages = get_spoken_languages_list()) !== null) {
        foreach ($languages as $loop_language) {
            $languages_arr[$loop_language->slug] = $loop_language->{'name_' . $language};
        }
    }

    $variables['languages'] = $languages_arr;
}

/* <> */
