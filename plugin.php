/**
 * Add CSV Import functionality for Projectsafes with custom column display and filter fix
 * Updated with fix for duplicate Source and Method fields
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
                                <li>First Name</li>
                                <li>Last Name</li>
                                <li>FullName</li>
                                <li>Phone</li>
                                <li>Email</li>
                                <li>Gender</li>
                                <li>DOB</li>
                                <li>Region</li>
                                <li>District</li>
                                <li>Address</li>
                                <li>Source</li>
                                <li>Method</li>
                                <li>Date</li>
                                <li>Type</li>
                                <li>Status (optional)</li>
                            </ol>
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
                
                var csvContent = "ID,First Name,Last Name,FullName,Phone,Email,Gender,DOB,Region,District,Address,Source,Method,Date,Type,Status\n";
                csvContent += "17458,Aman,Koushik,Aman Koushik,96919390,aditya@phype.co,Female,Tuesday February 5 1985,Hong Kong,Eastern,sjkbd dkfjndf kdfnjkfd,Karen Leung Foundation Website,Email,03-May-25,Project Safe,Request\n";
                csvContent += "17459,Jane,Smith,Jane Smith,87654321,jane@example.com,Female,1992-05-15,Hong Kong,Central,456 Park Ave,Referral,Phone,03-May-25,Project Teal,Completed";
                
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
    // Remove any leading "- " from the method field
    if (substr($method, 0, 2) === '- ') {
        return substr($method, 2);
    }
    
    // Check for duplicate method values (e.g., "Email-Email")
    if (preg_match('/^(Email|Phone|SMS|WhatsApp|WeChat)\-(Email|Phone|SMS|WhatsApp|WeChat)$/', $method)) {
        $parts = explode('-', $method);
        if ($parts[0] === $parts[1]) {
            return $parts[0];
        }
    }
    
    return $method;
}

// This function will clean up the Source field before saving
function clean_source_field($source) {
    if (empty($source)) {
        return $source;
    }

    // First remove any double spaces
    $source = preg_replace('/\s+/', ' ', trim($source));
    
    // Common source values that might be duplicated
    $known_sources = array(
        'Health Talk by Karen Leung Foundation',
        'Karen Leung Foundation',
        'School News',
        'Referral',
        'Website',
        'Email',
        'SMS',
        'Health Talk',
        'School NewsSchool News'
    );

    // Check for exact duplicates first
    foreach ($known_sources as $known_source) {
        if (stripos($source, $known_source . $known_source) !== false) {
            return $known_source;
        }
    }

    // Check for variations and clean them
    if (stripos($source, 'Health Talk by Karen Leung Foundation') !== false) {
        return 'Health Talk by Karen Leung Foundation';
    }
    
    if (stripos($source, 'School News') !== false) {
        return 'School News';
    }
    
    if (stripos($source, 'Karen Leung Foundation') !== false) {
        return 'Karen Leung Foundation';
    }

    // Remove any duplicate occurrences separated by spaces or other characters
    foreach ($known_sources as $known_source) {
        $pattern = '/(' . preg_quote($known_source, '/') . ')[^a-zA-Z0-9]*(' . preg_quote($known_source, '/') . ')/i';
        if (preg_match($pattern, $source)) {
            return $known_source;
        }
    }

    return $source;
}

// This function will clean up the Status field before saving
function clean_status_field($status) {
    if (empty($status)) {
        return $status;
    }

    // First remove any double spaces
    $status = preg_replace('/\s+/', ' ', trim($status));
    
    // Common status values that might be duplicated
    $known_statuses = array(
        'Out of QuotaOut of Quota' => 'Out of Quota',
        'Out of Quota Out of Quota' => 'Out of Quota',
        'PublishedPublished' => 'Published',
        'Published Published' => 'Published',
        'RequestRequest' => 'Request',
        'Request Request' => 'Request',
        'CompletedCompleted' => 'Completed',
        'Completed Completed' => 'Completed'
    );

    // Check for exact matches in known duplicates
    foreach ($known_statuses as $duplicate => $clean) {
        if (strcasecmp($status, $duplicate) === 0) {
            return $clean;
        }
    }

    // If the status is repeated, just return one instance
    $words = explode(' ', $status);
    if (count($words) >= 2 && $words[0] === $words[1]) {
        return $words[0];
    }

    return $status;
}

// Handle AJAX request for CSV import
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
        fgetcsv($handle);
    }
    
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $log = array();
    
    // Debug: Get all meta keys for a sample post to see what they should be
    if ($debug_mode) {
        $debug_post = get_posts(array(
            'post_type' => 'psyem-projectsafes',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($debug_post)) {
            $debug_post_id = $debug_post[0]->ID;
            $all_meta = get_post_meta($debug_post_id);
            $log[] = 'DEBUG: Found existing post ID ' . $debug_post_id . ' with meta keys: ' . implode(', ', array_keys($all_meta));
        }
    }
    
    // Process each row
    while (($data = fgetcsv($handle)) !== false) {
        // Make sure we have enough columns
        if (count($data) < 15) {
            $skipped++;
            $log[] = 'Skipped row: Not enough columns. Expected at least 15, got ' . count($data);
            continue;
        }
        
        // Map CSV columns to post fields based on the Excel format
        $id = !empty($data[0]) ? sanitize_text_field($data[0]) : '';
        $first_name = sanitize_text_field($data[1]);
        $last_name = sanitize_text_field($data[2]);
        $full_name = sanitize_text_field($data[3]);
        $phone = sanitize_text_field($data[4]);
        $email = sanitize_email($data[5]);
        $gender = sanitize_text_field($data[6]);
        $dob = sanitize_text_field($data[7]);
        $region = sanitize_text_field($data[8]);
        $district = sanitize_text_field($data[9]);
        $address = sanitize_text_field($data[10]);
        $source = clean_source_field(sanitize_text_field($data[11]));
        $method = clean_method_field(sanitize_text_field($data[12]));
        $date = sanitize_text_field($data[13]);
        
        // Standardize the type value
        $type = sanitize_text_field($data[14]);
        if (strtolower($type) === 'project safe' || strtolower($type) === 'project-safe') {
            $type = 'Project Safe';
        } else if (strtolower($type) === 'project teal' || strtolower($type) === 'project-teal') {
            $type = 'Project Teal';
        }
        
        // Status is optional (column 16)
        $status = isset($data[15]) ? clean_status_field(sanitize_text_field($data[15])) : '';
        
        // Use full_name as the post title, or combine first and last name if full_name is empty
        $post_title = !empty($full_name) ? $full_name : $first_name . ' ' . $last_name;
        
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
            update_post_meta($existing_post_id, 'psyem_projectsafe_dob', $dob); // Direct update
            update_post_meta($existing_post_id, 'psyem_projectsafe_region', $region);
            update_post_meta($existing_post_id, 'psyem_projectsafe_district', $district);
            update_post_meta($existing_post_id, 'psyem_projectsafe_address', $address);
            update_post_meta($existing_post_id, 'psyem_projectsafe_source', $source);
            update_post_meta($existing_post_id, 'psyem_projectsafe_method', $method); // Direct update
            update_post_meta($existing_post_id, 'psyem_projectsafe_date', $date);
            
            if (!empty($status)) {
                $clean_status = clean_status_field($status);
                update_post_meta($existing_post_id, 'psyem_projectsafe_status', $clean_status);
            }
            
            // Debug info
            if ($debug_mode) {
                $log[] = "DEBUG: Updated DOB value: '$dob'";
                $log[] = "DEBUG: Updated Method value: '$method'";
                $log[] = "DEBUG: Updated Source value: '$source'";
                $log[] = "DEBUG: Updated Type value: '$type'";
                $log[] = "DEBUG: After update, DOB = " . get_post_meta($existing_post_id, 'psyem_projectsafe_dob', true);
                $log[] = "DEBUG: After update, Method = " . get_post_meta($existing_post_id, 'psyem_projectsafe_method', true);
                $log[] = "DEBUG: After update, Source = " . get_post_meta($existing_post_id, 'psyem_projectsafe_source', true);
                $log[] = "DEBUG: After update, Type = " . get_post_meta($existing_post_id, 'psyem_projectsafe_type', true);
            }
            
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
            update_post_meta($post_id, 'psyem_projectsafe_dob', $dob); // Direct update
            update_post_meta($post_id, 'psyem_projectsafe_region', $region);
            update_post_meta($post_id, 'psyem_projectsafe_district', $district);
            update_post_meta($post_id, 'psyem_projectsafe_address', $address);
            update_post_meta($post_id, 'psyem_projectsafe_source', $source);
            update_post_meta($post_id, 'psyem_projectsafe_method', $method); // Direct update
            update_post_meta($post_id, 'psyem_projectsafe_date', $date);
            
            if (!empty($status)) {
                $clean_status = clean_status_field($status);
                update_post_meta($post_id, 'psyem_projectsafe_status', $clean_status);
            }
            
            // Debug info
            if ($debug_mode) {
                $log[] = "DEBUG: Set DOB value: '$dob'";
                $log[] = "DEBUG: Set Method value: '$method'";
                $log[] = "DEBUG: Set Source value: '$source'";
                $log[] = "DEBUG: Set Type value: '$type'";
                $log[] = "DEBUG: After creation, DOB = " . get_post_meta($post_id, 'psyem_projectsafe_dob', true);
                $log[] = "DEBUG: After creation, Method = " . get_post_meta($post_id, 'psyem_projectsafe_method', true);
                $log[] = "DEBUG: After creation, Source = " . get_post_meta($post_id, 'psyem_projectsafe_source', true);
                $log[] = "DEBUG: After creation, Type = " . get_post_meta($post_id, 'psyem_projectsafe_type', true);
            }
            
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

// Add custom column display handlers
function custom_projectsafes_column_display($column, $post_id) {
    switch ($column) {
        case 'psyem_projectsafe_dob':
            $dob = get_post_meta($post_id, 'psyem_projectsafe_dob', true);
            echo !empty($dob) ? esc_html($dob) : '-';
            break;
            
        case 'psyem_projectsafe_method':
            $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
            echo !empty($method) ? esc_html($method) : '-';
            break;
            
        case 'psyem_projectsafe_source':
            $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
            echo !empty($source) ? esc_html($source) : '-';
            break;
            
        case 'psyem_projectsafe_status':
            $status = get_post_meta($post_id, 'psyem_projectsafe_status', true);
            echo !empty($status) ? esc_html($status) : '-';
            break;
    }
}
add_action('manage_psyem-projectsafes_posts_custom_column', 'custom_projectsafes_column_display', 10, 2);

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

// Fix existing records with duplicate Source/Method values
function fix_duplicate_source_method_fields() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $updated = 0;
    
    foreach ($posts as $post_id) {
        $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
        $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
        
        // Check for duplications in source
        $cleaned_source = clean_source_field($source);
        if ($cleaned_source !== $source) {
            update_post_meta($post_id, 'psyem_projectsafe_source', $cleaned_source);
            $updated++;
        }
        
        // Check for duplications in method
        $cleaned_method = clean_method_field($method);
        if ($cleaned_method !== $method) {
            update_post_meta($post_id, 'psyem_projectsafe_method', $cleaned_method);
            $updated++;
        }
    }
    
    return $updated;
}

// Run this function once to fix existing records
function run_duplicate_field_fix() {
    $updated = fix_duplicate_source_method_fields();
    
    // Add admin notice
    if ($updated > 0) {
        add_action('admin_notices', function() use ($updated) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf('Fixed %d duplicate Source/Method values in Project Safe records.', $updated); ?></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'run_duplicate_field_fix');

// Debug function to check what's happening with the columns
function debug_projectsafes_columns() {
    $screen = get_current_screen();
    
    // Only run on the Projectsafes listing page
    if ($screen->id !== 'edit-psyem-projectsafes') {
        return;
    }
    
    // Get a sample post to check its meta
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => 1
    ));
    
    if (empty($posts)) {
        return;
    }
    
    $post_id = $posts[0]->ID;
    
    // Get all meta for this post
    $all_meta = get_post_meta($post_id);
    
    // Check specific fields
    $dob = get_post_meta($post_id, 'psyem_projectsafe_dob', true);
    $method = get_post_meta($post_id, 'psyem_projectsafe_method', true);
    $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
    $status = get_post_meta($post_id, 'psyem_projectsafe_status', true);
    $type = get_post_meta($post_id, 'psyem_projectsafe_type', true);
    
    // Output debug info
    ?>
    <div class="notice notice-info">
        <p><strong>Debug Info for Projectsafes Columns:</strong></p>
        <p>Post ID: <?php echo $post_id; ?></p>
        <p>Type value: "<?php echo esc_html($type); ?>"</p>
        <p>DOB value: "<?php echo esc_html($dob); ?>"</p>
        <p>Method value: "<?php echo esc_html($method); ?>"</p>
        <p>Source value: "<?php echo esc_html($source); ?>"</p>
        <p>Status value: "<?php echo esc_html($status); ?>"</p>
        <p>All meta keys: <?php echo implode(', ', array_keys($all_meta)); ?></p>
        <p>Current filter: <?php echo isset($_GET['projectsafe_type']) ? esc_html($_GET['projectsafe_type']) : 'None'; ?></p>
    </div>
    <?php
}
add_action('admin_notices', 'debug_projectsafes_columns');

// Force refresh of column data with JavaScript
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
        
        // Force refresh of column data
        $('.column-psyem_projectsafe_dob, .column-psyem_projectsafe_method, .column-psyem_projectsafe_source, .column-psyem_projectsafe_status').each(function() {
            var $cell = $(this);
            var postId = $cell.closest('tr').attr('id').replace('post-', '');
            var columnClass = $cell.attr('class').split(' ')[0];
            var columnName = columnClass.replace('column-', '');
            
            // Only refresh cells that are empty or have a dash
            if ($cell.text().trim() === '-' || $cell.text().trim() === '') {
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
            }
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
    
    // Return the value
    wp_send_json_success(!empty($value) ? esc_html($value) : '-');
}
add_action('wp_ajax_get_projectsafe_column_value', 'get_projectsafe_column_value_ajax');

// Update existing records to standardize type values
function update_projectsafe_type_values() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $updated = 0;
    
    foreach ($posts as $post_id) {
        $type = get_post_meta($post_id, 'psyem_projectsafe_type', true);
        
        // Standardize the type value
        if (strtolower($type) === 'project safe' || strtolower($type) === 'project-safe') {
            update_post_meta($post_id, 'psyem_projectsafe_type', 'Project Safe');
            $updated++;
        } else if (strtolower($type) === 'project teal' || strtolower($type) === 'project-teal') {
            update_post_meta($post_id, 'psyem_projectsafe_type', 'Project Teal');
            $updated++;
        }
    }
    
    // Add admin notice
    if ($updated > 0) {
        add_action('admin_notices', function() use ($updated) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf('Updated %d Project Safe type values for consistency.', $updated); ?></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'update_projectsafe_type_values');

// Fix existing records with duplicate Source/Method/Status values
function psyem_fix_duplicate_fields() {
    // Get all projectsafes posts
    $posts = get_posts(array(
        'post_type' => 'psyem-projectsafes',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $updated = 0;
    
    foreach ($posts as $post_id) {
        $source = get_post_meta($post_id, 'psyem_projectsafe_source', true);
        $status = get_post_meta($post_id, 'psyem_projectsafe_status', true);
        
        // Check for duplications in source
        $cleaned_source = clean_source_field($source);
        if ($cleaned_source !== $source) {
            update_post_meta($post_id, 'psyem_projectsafe_source', $cleaned_source);
            $updated++;
        }
        
        // Check for duplications in status
        if (!empty($status)) {
            $cleaned_status = clean_status_field($status);
            if ($cleaned_status !== $status) {
                update_post_meta($post_id, 'psyem_projectsafe_status', $cleaned_status);
                $updated++;
            }
        }
    }
    
    return $updated;
}

// Run this function once to fix existing records
function psyem_run_duplicate_field_fix() {
    $updated = psyem_fix_duplicate_fields();
    
    // Add admin notice
    if ($updated > 0) {
        add_action('admin_notices', function() use ($updated) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf('Fixed %d duplicate Source/Status values in Project Safe records.', $updated); ?></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'psyem_run_duplicate_field_fix');
