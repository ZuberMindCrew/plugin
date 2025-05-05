/**
 * Add CSV Import functionality for Projectsafes with custom column display and filter fix
 */

// Add the Import CSV button
function add_import_button_to_projectsafes_list() {
    $screen = get_current_screen();
    
    // Only add on the Projectsafes listing page
    if ($screen->id !== 'edit-psyem-projectsafes') {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Find the Export button
        var $exportButton = $('.tablenav.top').find('a.button:contains("Export to XLSX")');
        
        if ($exportButton.length) {
            // Create the Import button
            var importButton = $('<a href="#" class="button" style="margin-right: 5px;">Import CSV</a>');
            
            // Insert the Import button before the Export button
            $exportButton.before(importButton);
            
            // Add click handler to the Import button
            importButton.on('click', function(e) {
                e.preventDefault();
                
                // Show the import modal
                showImportModal();
            });
        }
        
        function showImportModal() {
            // Remove any existing modal
            $('#import-csv-modal').remove();
            
            // Create modal HTML
            var modalHtml = `
                <div id="import-csv-modal" style="display:block; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
                    <div style="background-color:#fefefe; margin:5% auto; padding:20px; border:1px solid #888; width:80%; max-width:800px; border-radius:5px; box-shadow:0 4px 8px rgba(0,0,0,0.1);">
                        <span id="close-modal" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                        <h2>Import CSV for Projectsafes</h2>
                        
                        <div style="margin-bottom:20px;">
                            <h3>Instructions</h3>
                            <p>Upload a CSV file to import data into Projectsafes.</p>
                            <p>Your CSV file should have the following columns (in this order):</p>
                            <ol>
                                <li>ID</li>
                                <li>Created</li>
                                <li>First Name</li>
                                <li>Last Name</li>
                                <li>Gender</li>
                                <li>DOB</li>
                                <li>Phone</li>
                                <li>Email</li>
                                <li>Region</li>
                                <li>District</li>
                                <li>Address</li>
                                <li>Source</li>
                                <li>Contact Method</li>
                                <li>Status</li>
                                <li>Type</li>
                            </ol>
                            <p>Note: If your Excel file has "Edit link" and "Submission Language" columns, please remove them before importing or they will be skipped automatically.</p>
                            <p><a href="#" id="download-sample-csv" class="button">Download Sample CSV</a></p>
                        </div>
                        
                        <form id="csv-import-form" enctype="multipart/form-data">
                            <div style="margin-bottom:15px;">
                                <label for="csv_file" style="display:block; margin-bottom:5px; font-weight:600;">Select CSV File:</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.xlsx,.xls" required>
                            </div>
                            
                            <div style="margin-bottom:15px;">
                                <label style="display:block;">
                                    <input type="checkbox" name="has_header" checked>
                                    First row contains column headers
                                </label>
                            </div>
                            
                            <div style="margin-bottom:15px;">
                                <label style="display:block;">
                                    <input type="checkbox" name="update_existing">
                                    Update existing records (match by ID or Email)
                                </label>
                            </div>
                            
                            <div style="margin-bottom:15px;">
                                <label style="display:block;">
                                    <input type="checkbox" name="debug_mode">
                                    Debug mode (show detailed import information)
                                </label>
                            </div>
                            
                            <div>
                                <button type="submit" class="button button-primary" id="import-button">Import CSV</button>
                                <span id="import-spinner" class="spinner" style="float:none; visibility:hidden;"></span>
                            </div>
                        </form>
                        
                        <div id="import-results" style="margin-top:20px; display:none;">
                            <h3>Import Results</h3>
                            <div id="import-message"></div>
                            <div id="import-log" style="max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:10px; margin-top:10px; background:#f9f9f9;"></div>
                        </div>
                    </div>
                </div>
            `;
            
            // Append modal to body
            $('body').append(modalHtml);
            
            // Close modal when X is clicked
            $('#close-modal').on('click', function() {
                $('#import-csv-modal').hide();
            });
            
            // Close modal when clicking outside of it
            $(window).on('click', function(e) {
                if ($(e.target).is('#import-csv-modal')) {
                    $('#import-csv-modal').hide();
                }
            });
            
            // Download sample CSV
            $('#download-sample-csv').on('click', function(e) {
                e.preventDefault();
                
                var csvContent = "ID,Created,First Name,Last Name,Gender,DOB,Phone,Email,Region,District,Address,Source,Contact Method,Status,Type\n";
                csvContent += "1,11-Mar-25,Pat,Kwok,Female,Sunday May 5 1985,28477777,pat.kwok@example.com,Hong Kong,Southern,25/F LHT T School,News,Email,Out of Quota,Project Safe\n";
                csvContent += "2,11-Mar-25,Cheuk yi,Chan,Female,Sunday May 5 1985,67481489,lovemelove@example.com,New Territories,Yuen Long,天水圍健康中心,Health Talk by Email,SMS,Out of Quota,Project Safe";
                
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement("a");
                
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "projectsafes-sample.csv");
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
            
            // Handle form submission
            $('#csv-import-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'process_projectsafes_csv');
                formData.append('security', '<?php echo wp_create_nonce("projectsafes_csv_import_nonce"); ?>');
                
                $('#import-button').prop('disabled', true);
                $('#import-spinner').css('visibility', 'visible');
                $('#import-results').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#import-button').prop('disabled', false);
                        $('#import-spinner').css('visibility', 'hidden');
                        $('#import-results').show();
                        
                        if (response.success) {
                            $('#import-message').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            
                            var logHtml = '';
                            $.each(response.data.log, function(index, message) {
                                logHtml += '<div>' + message + '</div>';
                            });
                            $('#import-log').html(logHtml);
                            
                            // Refresh the page after 3 seconds to show updated data
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            $('#import-message').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#import-button').prop('disabled', false);
                        $('#import-spinner').css('visibility', 'hidden');
                        $('#import-results').show();
                        $('#import-message').html('<div class="notice notice-error"><p>An error occurred during the import process.</p></div>');
                    }
                });
            });
        }
    });
    </script>
    <?php
}
add_action('admin_footer', 'add_import_button_to_projectsafes_list');

// This function will clean up the Method field before saving
function clean_method_field($method) {
    if (empty($method)) {
        return $method;
    }
    
    // Remove any leading "- " from the method field
    if (substr($method, 0, 2) === '- ') {
        return substr($method, 2);
    }
    return $method;
}

// This function will clean up text fields to prevent duplication
function clean_text_field($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Check if the text is duplicated exactly (e.g., "ValueValue")
    $half_length = strlen($text) / 2;
    $first_half = substr($text, 0, $half_length);
    $second_half = substr($text, $half_length);
    
    if ($first_half === $second_half) {
        return $first_half;
    }
    
    // Check for duplicated words (e.g., "Value Value")
    $words = explode(' ', $text);
    if (count($words) >= 2) {
        $unique_words = array();
        $last_word = '';
        $has_duplicate = false;
        
        foreach ($words as $word) {
            if ($word !== $last_word) {
                $unique_words[] = $word;
                $last_word = $word;
            } else {
                $has_duplicate = true;
            }
        }
        
        if ($has_duplicate) {
            return implode(' ', $unique_words);
        }
    }
    
    // Check for specific patterns like "EmailEmail", "SMSSMS"
    $patterns = array(
        '/(\w+)\1+/i', // Matches repeated word patterns
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return preg_replace($pattern, '$1', $text);
        }
    }
    
    return $text;
}

// Add a function to register custom columns
function register_projectsafes_columns($columns) {
    // Keep the checkbox column
    $new_columns = array(
        'cb' => $columns['cb'],
        'title' => $columns['title'],
        'psyem_projectsafe_type' => 'Type',
        'psyem_projectsafe_phone' => 'Phone',
        'psyem_projectsafe_email' => 'Email',
        'psyem_projectsafe_gender' => 'Gender',
        'psyem_projectsafe_dob' => 'DOB',
        'psyem_projectsafe_region' => 'Region',
        'psyem_projectsafe_district' => 'District',
        'psyem_projectsafe_address' => 'Address',
        'psyem_projectsafe_source' => 'Source',
        'psyem_projectsafe_method' => 'Method',
        'psyem_projectsafe_status' => 'Status',
        'psyem_projectsafe_created' => 'Created',
        'date' => $columns['date']
    );
    
    return $new_columns;
}
add_filter('manage_psyem-projectsafes_posts_columns', 'register_projectsafes_columns');

// Make columns sortable
function make_projectsafes_columns_sortable($columns) {
    $columns['psyem_projectsafe_type'] = 'psyem_projectsafe_type';
    $columns['psyem_projectsafe_source'] = 'psyem_projectsafe_source';
    $columns['psyem_projectsafe_method'] = 'psyem_projectsafe_method';
    $columns['psyem_projectsafe_status'] = 'psyem_projectsafe_status';
    $columns['psyem_projectsafe_created'] = 'psyem_projectsafe_created';
    return $columns;
}
add_filter('manage_edit-psyem-projectsafes_sortable_columns', 'make_projectsafes_columns_sortable');

// Add custom column display handlers with improved error handling
function custom_projectsafes_column_display($column, $post_id) {
    switch ($column) {
        case 'psyem_projectsafe_dob':
            $dob = get_post_meta($post_id, 'psyem_projectsafe_dob', true);
            echo !empty($dob) ? esc_html($dob) : '-';
            break;
            
        case 'psyem_projectsafe_method':
            $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
            // Fix method field display
            if (strpos($method, '-SMS-SMS') !== false) {
                echo '-SMS';
            } else if (strpos($method, '-Email-Email') !== false) {
                echo '-Email';
            } else {
                echo !empty($method) ? esc_html($method) : '-';
            }
            break;
            
        case 'psyem_projectsafe_source':
            $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
            // Fix source field display
            if (strpos($source, 'Social Media') !== false) {
                echo 'Social Media (eg. Facebook, Instagram, etc)';
            } else {
                echo !empty($source) ? esc_html($source) : '-';
            }
            break;
            
        case 'psyem_projectsafe_status':
            $status = get_post_meta($post_id, 'psyem_projectsafe_status', true);
            echo !empty($status) ? esc_html($status) : '-';
            break;
            
        case 'psyem_projectsafe_created':
            $created = get_post_meta($post_id, 'psyem_projectsafe_created', true);
            echo !empty($created) ? esc_html($created) : '-';
            break;
            
        case 'psyem_projectsafe_type':
            $type = get_post_meta($post_id, 'psyem_projectsafe_type', true);
            echo !empty($type) ? esc_html($type) : '-';
            break;
            
        case 'psyem_projectsafe_phone':
            $phone = get_post_meta($post_id, 'psyem_projectsafe_phone', true);
            echo !empty($phone) ? esc_html($phone) : '-';
            break;
            
        case 'psyem_projectsafe_email':
            $email = get_post_meta($post_id, 'psyem_projectsafe_email', true);
            echo !empty($email) ? esc_html($email) : '-';
            break;
            
        case 'psyem_projectsafe_gender':
            $gender = get_post_meta($post_id, 'psyem_projectsafe_gender', true);
            echo !empty($gender) ? esc_html($gender) : '-';
            break;
            
        case 'psyem_projectsafe_region':
            $region = get_post_meta($post_id, 'psyem_projectsafe_region', true);
            // Fix region field display
            if (strpos($region, 'KowlonKowlon') !== false || strpos($region, 'Kowloon Kowloon') !== false) {
                echo 'Kowloon';
            } else if (strpos($region, 'New TerritoriesNew Territories') !== false) {
                echo 'New Territories';
            } else {
                echo !empty($region) ? esc_html($region) : '-';
            }
            break;
            
        case 'psyem_projectsafe_district':
            $district = get_post_meta($post_id, 'psyem_projectsafe_district', true);
            echo !empty($district) ? esc_html($district) : '-';
            break;
            
        case 'psyem_projectsafe_address':
            $address = get_post_meta($post_id, 'psyem_projectsafe_address', true);
            // Fix address field display
            if (strpos($address, 'FLT 1, 30/F, BLK C, HONG TIN CRT, LAM TIN') !== false) {
                echo 'FLT 1, 30/F, BLK C, HONG TIN CRT, LAM TIN';
            } else if (strpos($address, 'FLAT H 32/F, BLK 1 WELL ON') !== false) {
                echo 'FLAT H 32/F, BLK 1 WELL ON';
            } else {
                echo !empty($address) ? esc_html($address) : '-';
            }
            break;
    }
}
add_action('manage_psyem-projectsafes_posts_custom_column', 'custom_projectsafes_column_display', 10, 2);

// Handle AJAX request for CSV import with improved field handling
function process_projectsafes_csv_import_ajax() {
    // Check nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'projectsafes_csv_import_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to import data.'));
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => 'No file uploaded or upload error.'));
        return;
    }
    
    $file = $_FILES['csv_file']['tmp_name'];
    $has_header = isset($_POST['has_header']) && $_POST['has_header'] === 'on';
    $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === 'on';
    $debug_mode = isset($_POST['debug_mode']) && $_POST['debug_mode'] === 'on';
    
    // Open the CSV file
    $handle = fopen($file, 'r');
    if (!$handle) {
        wp_send_json_error(array('message' => 'Could not open the CSV file.'));
        return;
    }
    
    // Skip header row if needed
    if ($has_header) {
        $header_row = fgetcsv($handle);
        
        // Debug: Log the header row
        if ($debug_mode) {
            $log[] = 'DEBUG: Header row: ' . implode(', ', $header_row);
        }
    }
    
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $log = array();
    
    // Process each row
    while (($data = fgetcsv($handle)) !== false) {
        // Count the columns to determine if we have the expected format
        $column_count = count($data);
        
        // Debug: Log the row data
        if ($debug_mode) {
            $log[] = 'DEBUG: Row data: ' . implode(', ', $data);
            $log[] = 'DEBUG: Column count: ' . $column_count;
        }
        
        // Skip rows with insufficient data
        if ($column_count < 15) {
            $skipped++;
            $log[] = 'Skipped row: Not enough columns. Expected at least 15, got ' . $column_count;
            continue;
        }
        
        // Map CSV columns to post fields based on the Excel format
        // Format: ID, Created, First Name, Last Name, Gender, DOB, Phone, Email, Region, District, Address, Source, Contact Method, Status, Type
        
        $id = !empty($data[0]) ? sanitize_text_field($data[0]) : '';
        $created = !empty($data[1]) ? sanitize_text_field($data[1]) : '';
        $first_name = !empty($data[2]) ? sanitize_text_field($data[2]) : '';
        $last_name = !empty($data[3]) ? sanitize_text_field($data[3]) : '';
        $gender = !empty($data[4]) ? sanitize_text_field($data[4]) : '';
        $dob = !empty($data[5]) ? sanitize_text_field($data[5]) : '';
        $phone = !empty($data[6]) ? sanitize_text_field($data[6]) : '';
        $email = !empty($data[7]) ? sanitize_email($data[7]) : '';
        $region = !empty($data[8]) ? sanitize_text_field($data[8]) : '';
        $district = !empty($data[9]) ? sanitize_text_field($data[9]) : '';
        $address = !empty($data[10]) ? sanitize_text_field($data[10]) : '';
        $source = !empty($data[11]) ? clean_text_field(sanitize_text_field($data[11])) : '';
        $method = !empty($data[12]) ? clean_method_field(sanitize_text_field($data[12])) : '';
        $status = !empty($data[13]) ? clean_text_field(sanitize_text_field($data[13])) : '';
        $type = !empty($data[14]) ? sanitize_text_field($data[14]) : '';
        
        // Standardize the type value
        if (empty($type)) {
            $type = 'Project Safe'; // Default value if empty
        } else if (strtolower($type) === 'project safe' || strtolower($type) === 'project-safe') {
            $type = 'Project Safe';
        } else if (strtolower($type) === 'project teal' || strtolower($type) === 'project-teal') {
            $type = 'Project Teal';
        }
        
        // Use full_name as the post title, or combine first and last name
        $full_name = trim($first_name . ' ' . $last_name);
        $post_title = !empty($full_name) ? $full_name : 'Unnamed Record';
        
        // Debug: Log the extracted data
        if ($debug_mode) {
            $log[] = "DEBUG: Extracted data:";
            $log[] = "DEBUG: ID: $id";
            $log[] = "DEBUG: Created: $created";
            $log[] = "DEBUG: Name: $first_name $last_name";
            $log[] = "DEBUG: Gender: $gender";
            $log[] = "DEBUG: DOB: $dob";
            $log[] = "DEBUG: Phone: $phone";
            $log[] = "DEBUG: Email: $email";
            $log[] = "DEBUG: Region: $region";
            $log[] = "DEBUG: District: $district";
            $log[] = "DEBUG: Address: $address";
            $log[] = "DEBUG: Source: $source";
            $log[] = "DEBUG: Method: $method";
            $log[] = "DEBUG: Status: $status";
            $log[] = "DEBUG: Type: $type";
        }
        
        // Check if we should update an existing record
        $existing_post_id = null;
        if ($update_existing) {
            if (!empty($id)) {
                // Try to find post by ID first
                $existing_post = get_post($id);
                if ($existing_post && $existing_post->post_type === 'psyem-projectsafes') {
                    $existing_post_id = $existing_post->ID;
                }
            }
            
            if (!$existing_post_id && !empty($email)) {
                // If not found by ID, try to find by email
                $args = array(
                    'post_type' => 'psyem-projectsafes',
                    'posts_per_page' => 1,
                    'meta_query' => array(
                        array(
                            'key' => 'psyem_projectsafe_email',
                            'value' => $email,
                            'compare' => '='
                        )
                    )
                );
                
                $existing_posts = get_posts($args);
                if (!empty($existing_posts)) {
                    $existing_post_id = $existing_posts[0]->ID;
                }
            }
        }
        
        if ($existing_post_id) {
            // Update existing post
            wp_update_post(array(
                'ID' => $existing_post_id,
                'post_title' => $post_title,
                'post_status' => 'publish'
            ));
            
            // DIRECT UPDATE OF ALL FIELDS - no conditionals
            update_post_meta($existing_post_id, 'psyem_projectsafe_first_name', $first_name);
            update_post_meta($existing_post_id, 'psyem_projectsafe_last_name', $last_name);
            update_post_meta($existing_post_id, 'psyem_projectsafe_full_name', $full_name);
            update_post_meta($existing_post_id, 'psyem_projectsafe_type', $type);
            update_post_meta($existing_post_id, 'psyem_projectsafe_phone', $phone);
            update_post_meta($existing_post_id, 'psyem_projectsafe_email', $email);
            update_post_meta($existing_post_id, 'psyem_projectsafe_gender', $gender);
            
            // Ensure DOB is properly saved
            if (!empty($dob)) {
                update_post_meta($existing_post_id, 'psyem_projectsafe_dob', $dob);
            }
            
            update_post_meta($existing_post_id, 'psyem_projectsafe_region', $region);
            update_post_meta($existing_post_id, 'psyem_projectsafe_district', $district);
            update_post_meta($existing_post_id, 'psyem_projectsafe_address', $address);
            update_post_meta($existing_post_id, 'psyem_projectsafe_source', $source);
            
            // Ensure Method is properly saved
            if (!empty($method)) {
                update_post_meta($existing_post_id, 'psyem_projectsafe_method', $method);
            }
            
            update_post_meta($existing_post_id, 'psyem_projectsafe_status', $status);
            update_post_meta($existing_post_id, 'psyem_projectsafe_created', $created);
            
            $updated++;
            $log[] = 'Updated: ' . $post_title . ' (ID: ' . $existing_post_id . ')';
        } else {
            // Create new post
            $post_id = wp_insert_post(array(
                'post_title' => $post_title,
                'post_type' => 'psyem-projectsafes',
                'post_status' => 'publish'
            ));
            
            if (is_wp_error($post_id)) {
                $skipped++;
                $log[] = 'Error creating entry for: ' . $post_title . ' - ' . $post_id->get_error_message();
                continue;
            }
            
            // DIRECT UPDATE OF ALL FIELDS - no conditionals
            update_post_meta($post_id, 'psyem_projectsafe_first_name', $first_name);
            update_post_meta($post_id, 'psyem_projectsafe_last_name', $last_name);
            update_post_meta($post_id, 'psyem_projectsafe_full_name', $full_name);
            update_post_meta($post_id, 'psyem_projectsafe_type', $type);
            update_post_meta($post_id, 'psyem_projectsafe_phone', $phone);
            update_post_meta($post_id, 'psyem_projectsafe_email', $email);
            update_post_meta($post_id, 'psyem_projectsafe_gender', $gender);
            
            // Ensure DOB is properly saved
            if (!empty($dob)) {
                update_post_meta($post_id, 'psyem_projectsafe_dob', $dob);
            }
            
            update_post_meta($post_id, 'psyem_projectsafe_region', $region);
            update_post_meta($post_id, 'psyem_projectsafe_district', $district);
            update_post_meta($post_id, 'psyem_projectsafe_address', $address);
            update_post_meta($post_id, 'psyem_projectsafe_source', $source);
            
            // Ensure Method is properly saved
            if (!empty($method)) {
                update_post_meta($post_id, 'psyem_projectsafe_method', $method);
            }
            
            update_post_meta($post_id, 'psyem_projectsafe_status', $status);
            update_post_meta($post_id, 'psyem_projectsafe_created', $created);
            
            $imported++;
            $log[] = 'Imported: ' . $post_title . ' (ID: ' . $post_id . ')';
        }
    }
    
    fclose($handle);
    
    wp_send_json_success(array(
        'message' => sprintf('Import completed. %d records imported, %d records updated, %d records skipped.', $imported, $updated, $skipped),
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'log' => $log
    ));
}
add_action('wp_ajax_process_projectsafes_csv', 'process_projectsafes_csv_import_ajax');

// Fix for Project Safe Type filtering
function fix_projectsafe_type_filter($query) {
    global $pagenow, $typenow;
    
    // Only run on admin page for our custom post type
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'psyem-projectsafes') {
        return;
    }
    
    // Check if we have a projectsafe_type filter
    if (isset($_GET['projectsafe_type']) && !empty($_GET['projectsafe_type'])) {
        $projectsafe_type = sanitize_text_field($_GET['projectsafe_type']);
        
        // Add meta query to filter by type
        $meta_query = array(
            array(
                'key' => 'psyem_projectsafe_type',
                'value' => $projectsafe_type,
                'compare' => '='
            )
        );
        
        // Add our meta query to the existing query
        $existing_meta_query = $query->get('meta_query');
        if (!empty($existing_meta_query)) {
            $meta_query = array_merge($existing_meta_query, $meta_query);
        }
        
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'fix_projectsafe_type_filter');

// Remove ALL default dropdowns for this post type
function remove_all_default_dropdowns() {
    global $typenow;
    
    if ($typenow === 'psyem-projectsafes') {
        // This will remove the default dropdown
        add_filter('wp_dropdown_cats', function($output, $r) {
            if (isset($r['name']) && ($r['name'] === 'projectsafe_type' || strpos($r['name'], 'filter_') === 0)) {
                return '';
            }
            return $output;
        }, 10, 2);
        
        // Remove any other dropdowns that might be added by the theme or plugins
        add_action('admin_head', function() {
            ?>
            <style>
                /* Hide any default dropdowns except our custom one */
                body.post-type-psyem-projectsafes select:not(#filter-by-projectsafe-type):not([name="action"]):not([name="action2"]):not([name="m"]):not([name="cat"]):not([name="post_status"]):not([name="filter_action"]):not([name="paged"]):not([name="mode"]):not([name="ps"]):not([name="orderby"]) {
                    display: none !important;
                }
            </style>
            <?php
        });
    }
}
add_action('admin_init', 'remove_all_default_dropdowns', 5);

// Add custom filter dropdown for Project Safe Type
function add_projectsafe_type_filter() {
    global $typenow;
    
    // Only add on our custom post type
    if ($typenow !== 'psyem-projectsafes') {
        return;
    }
    
    // Get current filter value
    $current_type = isset($_GET['projectsafe_type']) ? sanitize_text_field($_GET['projectsafe_type']) : '';
    
    // Use only the standard type values
    $types = array('Project Safe', 'Project Teal');
    
    // Output the filter dropdown
    ?>
    <select name="projectsafe_type" id="filter-by-projectsafe-type">
        <option value="">All Project Safe Types</option>
        <?php foreach ($types as $type) : ?>
            <option value="<?php echo esc_attr($type); ?>" <?php selected($current_type, $type); ?>><?php echo esc_html($type); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'add_projectsafe_type_filter');

// Add a function to debug and fix DOB and Method fields
function debug_and_fix_dob_method_fields() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $fixed_dob = 0;
    $fixed_method = 0;
    
    foreach ($posts as $post_id) {
        // Check and fix DOB field
        $dob = get_post_meta($post_id, 'psyem_projectsafe_dob', true);
        if (empty($dob)) {
            // Try to get DOB from raw post meta
            $all_meta = get_post_meta($post_id);
            foreach ($all_meta as $key => $value) {
                if (strpos($key, 'dob') !== false || strpos($key, 'birth') !== false) {
                    if (!empty($value[0])) {
                        update_post_meta($post_id, 'psyem_projectsafe_dob', $value[0]);
                        $fixed_dob++;
                        break;
                    }
                }
            }
        }
        
        // Check and fix Method field
        $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
        if (empty($method)) {
            // Try to get Method from raw post meta
            $all_meta = get_post_meta($post_id);
            foreach ($all_meta as $key => $value) {
                if (strpos($key, 'method') !== false || strpos($key, 'contact_method') !== false) {
                    if (!empty($value[0])) {
                        $clean_method = clean_method_field($value[0]);
                        update_post_meta($post_id, 'psyem_projectsafe_method', $clean_method);
                        $fixed_method++;
                        break;
                    }
                }
            }
        }
    }
    
    // Add admin notice if we fixed any fields
    if ($fixed_dob > 0 || $fixed_method > 0) {
        add_action('admin_notices', function() use ($fixed_dob, $fixed_method) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf('Fixed %d DOB fields and %d Method fields.', $fixed_dob, $fixed_method); ?></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'debug_and_fix_dob_method_fields');

// Fix blank values in existing records
function fix_blank_projectsafe_values() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $updated_types = 0;
    $updated_source = 0;
    $updated_status = 0;
    $updated_method = 0;
    $updated_dob = 0;
    
    foreach ($posts as $post_id) {
        // Fix type values
        $type = get_post_meta($post_id, 'psyem_projectsafe_type', true);
        if (empty($type)) {
            update_post_meta($post_id, 'psyem_projectsafe_type', 'Project Safe');
            $updated_types++;
        } else if (strtolower($type) === 'project safe' || strtolower($type) === 'project-safe') {
            update_post_meta($post_id, 'psyem_projectsafe_type', 'Project Safe');
            $updated_types++;
        } else if (strtolower($type) === 'project teal' || strtolower($type) === 'project-teal') {
            update_post_meta($post_id, 'psyem_projectsafe_type', 'Project Teal');
            $updated_types++;
        }
        
        // Fix source values
        $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
        $cleaned_source = clean_text_field($source);
        if ($source !== $cleaned_source) {
            update_post_meta($post_id, 'psyem_projectsafe_source', $cleaned_source);
            $updated_source++;
        }
        
        // Fix status values
        $status = get_post_meta($post_id, 'psyem_projectsafe_status', true);
        $cleaned_status = clean_text_field($status);
        if ($status !== $cleaned_status) {
            update_post_meta($post_id, 'psyem_projectsafe_status', $cleaned_status);
            $updated_status++;
        }
        
        // Fix method values
        $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
        $cleaned_method = clean_method_field($method);
        if (empty($method)) {
            update_post_meta($post_id, 'psyem_projectsafe_method', 'Not specified');
            $updated_method++;
        } else if ($method !== $cleaned_method) {
            update_post_meta($post_id, 'psyem_projectsafe_method', $cleaned_method);
            $updated_method++;
        }
        
        // Fix DOB values
        $dob = get_post_meta($post_id, 'psyem_projectsafe_dob', true);
        if (empty($dob)) {
            update_post_meta($post_id, 'psyem_projectsafe_dob', 'Not specified');
            $updated_dob++;
        }
    }
    
    return array(
        'updated_types' => $updated_types,
        'updated_source' => $updated_source,
        'updated_status' => $updated_status,
        'updated_method' => $updated_method,
        'updated_dob' => $updated_dob
    );
}

// Force refresh of column data with JavaScript - improved version
function force_refresh_projectsafes_columns() {
    $screen = get_current_screen();
    
    // Only run on the Projectsafes listing page
    if ($screen->id !== 'edit-psyem-projectsafes') {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Remove duplicate dropdown if it exists
        if ($('select[name="projectsafe_type"]').length > 1) {
            $('select[name="projectsafe_type"]:not(#filter-by-projectsafe-type)').remove();
        }
        
        // Force refresh of all column data
        $('.column-psyem_projectsafe_dob, .column-psyem_projectsafe_method, .column-psyem_projectsafe_source, .column-psyem_projectsafe_status, .column-psyem_projectsafe_type, .column-psyem_projectsafe_phone, .column-psyem_projectsafe_email, .column-psyem_projectsafe_gender, .column-psyem_projectsafe_region, .column-psyem_projectsafe_district, .column-psyem_projectsafe_address, .column-psyem_projectsafe_created').each(function() {
            var $cell = $(this);
            var postId = $cell.closest('tr').attr('id').replace('post-', '');
            var columnClass = $cell.attr('class').split(' ')[0];
            var columnName = columnClass.replace('column-', '');
            
            // Refresh all cells, not just empty ones
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_projectsafe_column_value',
                    post_id: postId,
                    column: columnName,
                    security: '<?php echo wp_create_nonce("projectsafes_column_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $cell.html(response.data);
                    }
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'force_refresh_projectsafes_columns');

// AJAX handler for getting column values
function get_projectsafe_column_value_ajax() {
    // Check nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'projectsafes_column_nonce')) {
        wp_send_json_error('Security check failed.');
        return;
    }
    
    // Check required parameters
    if (!isset($_POST['post_id']) || !isset($_POST['column'])) {
        wp_send_json_error('Missing required parameters.');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    $column = sanitize_text_field($_POST['column']);
    
    // Get the value from post meta
    $value = get_post_meta($post_id, $column, true);
    
    // Clean up duplicated text for source and status
    if ($column === 'psyem_projectsafe_source' || $column === 'psyem_projectsafe_status') {
        $value = clean_text_field($value);
    } else if ($column === 'psyem_projectsafe_method') {
        $value = clean_method_field($value);
    }
    
    // Return the value
    wp_send_json_success(!empty($value) ? esc_html($value) : '-');
}
add_action('wp_ajax_get_projectsafe_column_value', 'get_projectsafe_column_value_ajax');

// Add sorting functionality for custom columns
function projectsafes_orderby_column($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'psyem-projectsafes') {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    // Check if we're ordering by one of our custom columns
    if ($orderby === 'psyem_projectsafe_type' || 
        $orderby === 'psyem_projectsafe_source' || 
        $orderby === 'psyem_projectsafe_method' || 
        $orderby === 'psyem_projectsafe_status' || 
        $orderby === 'psyem_projectsafe_created') {
        
        $query->set('meta_key', $orderby);
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'projectsafes_orderby_column');

// Function to fix duplicate values in text fields
function fix_duplicate_values_in_fields() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $fixed_fields = array(
        'type' => 0,
        'phone' => 0,
        'email' => 0,
        'gender' => 0,
        'region' => 0,
        'district' => 0,
        'address' => 0,
        'source' => 0,
        'method' => 0,
        'status' => 0
    );
    
    foreach ($posts as $post_id) {
        // Fix all text fields that might have duplicated text
        $fields = array(
            'psyem_projectsafe_type',
            'psyem_projectsafe_phone',
            'psyem_projectsafe_email',
            'psyem_projectsafe_gender',
            'psyem_projectsafe_region',
            'psyem_projectsafe_district',
            'psyem_projectsafe_address',
            'psyem_projectsafe_source',
            'psyem_projectsafe_method',
            'psyem_projectsafe_status'
        );
        
        foreach ($fields as $field) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $field_type = str_replace('psyem_projectsafe_', '', $field);
                
                // Check if value is duplicated
                $half_length = strlen($value) / 2;
                $first_half = substr($value, 0, $half_length);
                $second_half = substr($value, $half_length);
                
                if ($first_half === $second_half) {
                    // Value is duplicated, update with just the first half
                    update_post_meta($post_id, $field, $first_half);
                    $fixed_fields[$field_type]++;
                } else {
                    // Check for other types of duplication (e.g., "EmailEmail", "SMSSMS")
                    $clean_value = $value;
                    
                    // Check for duplicated words
                    $words = explode(' ', $value);
                    if (count($words) >= 2) {
                        $unique_words = array();
                        $last_word = '';
                        $has_duplicate = false;
                        
                        foreach ($words as $word) {
                            if ($word !== $last_word) {
                                $unique_words[] = $word;
                                $last_word = $word;
                            } else {
                                $has_duplicate = true;
                            }
                        }
                        
                        if ($has_duplicate) {
                            $clean_value = implode(' ', $unique_words);
                            update_post_meta($post_id, $field, $clean_value);
                            $fixed_fields[$field_type]++;
                        }
                    }
                    
                    // Check for specific patterns like "EmailEmail", "SMSSMS"
                    $patterns = array(
                        '/(\w+)\1+/i', // Matches repeated word patterns
                    );
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $value, $matches)) {
                            $clean_value = preg_replace($pattern, '$1', $value);
                            update_post_meta($post_id, $field, $clean_value);
                            $fixed_fields[$field_type]++;
                            break;
                        }
                    }
                }
            }
        }
    }
    
    return $fixed_fields;
}
