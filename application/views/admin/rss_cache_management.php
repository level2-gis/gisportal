<?php $this->load->view('templates/header', $data); ?>
<?php $this->load->view('templates/header_navigation', $data); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2><?php echo $title; ?></h2>
            
            <?php if ($this->session->flashdata('message')): ?>
                <div class="alert alert-<?php echo ($this->session->flashdata('message_type') == 'error') ? 'danger' : $this->session->flashdata('message_type'); ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?php echo $this->session->flashdata('message'); ?>
                </div>
            <?php endif; ?>
            
            <!-- RSS Configuration Status -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">RSS Feed Configuration</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>RSS Feed URL:</strong></td>
                                    <td><?php echo !empty($rss_url) ? $rss_url : '<em>Not configured</em>'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Cache Enabled:</strong></td>
                                    <td>
                                        <span class="label label-<?php echo $cache_enabled ? 'success' : 'warning'; ?>">
                                            <?php echo $cache_enabled ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Cache Duration:</strong></td>
                                    <td><?php echo $cache_duration; ?> seconds (<?php echo round($cache_duration / 60, 1); ?> minutes)</td>
                                </tr>
                                <tr>
                                    <td><strong>Cache Directory:</strong></td>
                                    <td><?php echo $cache_dir; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <?php if (!empty($rss_url)): ?>
                                    <a href="<?php echo site_url('rss_cache/refresh_feed'); ?>" class="btn btn-primary btn-lg">
                                        <i class="fa fa-refresh"></i> Refresh RSS Feed
                                    </a>
                                    <br><br>
                                    <a href="<?php echo site_url('rss_cache/clear_feed'); ?>" class="btn btn-warning">
                                        <i class="fa fa-trash"></i> Clear RSS Cache
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <strong>Note:</strong> RSS feed URL is not configured. 
                                        Please set <code>rss_feed_url</code> in your configuration file.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cache Files Status -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        Cache Files Status 
                        <span class="badge"><?php echo count($cache_files); ?> files</span>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($cache_files)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> No cache files found.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-right">
                                    <a href="<?php echo site_url('rss_cache/clear_all'); ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to clear all RSS cache files?');">
                                        <i class="fa fa-trash"></i> Clear All Cache
                                    </a>
                                </div>
                                <div class="clearfix"></div>
                                <br>
                            </div>
                        </div>
                        
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                    <th>Age</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cache_files as $file): ?>
                                    <?php 
                                        $is_expired = $file['age'] > $cache_duration;
                                        $age_hours = round($file['age'] / 3600, 1);
                                        $size_kb = round($file['size'] / 1024, 1);
                                    ?>
                                    <tr>
                                        <td>
                                            <code><?php echo htmlspecialchars($file['name']); ?></code>
                                        </td>
                                        <td><?php echo $size_kb; ?> KB</td>
                                        <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                                        <td><?php echo $age_hours; ?> hours</td>
                                        <td>
                                            <span class="label label-<?php echo $is_expired ? 'danger' : 'success'; ?>">
                                                <?php echo $is_expired ? 'Expired' : 'Valid'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Cache Configuration Guide -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Configuration Guide</h3>
                </div>
                <div class="panel-body">
                    <p>To configure RSS caching, add the following settings to your <code>application/config/gisportal.php</code> file:</p>
                    <pre><code>// RSS Feed Caching Configuration
$config['rss_cache_enabled'] = TRUE;           // Enable/disable RSS caching
$config['rss_cache_duration'] = 3600;          // Cache duration in seconds (1 hour)
$config['rss_cache_dir'] = APPPATH . 'cache/rss/'; // Cache directory</code></pre>
                    
                    <h4>Recommended Cache Durations:</h4>
                    <ul>
                        <li><strong>300 seconds (5 minutes)</strong> - For frequently updated news feeds</li>
                        <li><strong>1800 seconds (30 minutes)</strong> - For regular news updates</li>
                        <li><strong>3600 seconds (1 hour)</strong> - For daily news (recommended default)</li>
                        <li><strong>21600 seconds (6 hours)</strong> - For less frequently updated content</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>
