<?php
class Tools extends CI_Controller
{

	public function index()
	{
		echo 'Tools controller for running tasks on server with Command line interface' . PHP_EOL;
	}

	/*
	 * This script will create missing thumbnail images for every client subproject folder in uploads folder.
	 * If images are in project subfolder add it to second parameter
	 * Usage:
	 * Open server cmd in gisportal folder
	 * php index.php tools image_thumb_fix [client_name] [optional_subfolder]
	 */
	public function image_thumb_fix($client_name = FALSE, $subfolder = NULL)
	{
		try {
			if (!is_cli()) {
				throw new Exception('You have to run this only through command line interface!' . PHP_EOL . 'TODO Write short how-to' . PHP_EOL);
			}
			if (empty($client_name)) {
				throw new Exception ('Client name missing!' . PHP_EOL);
			}
			//check client and upload dir
			$this->load->model('client_model');
			$this->load->helper(array('path','file'));
			if(!$this->client_model->client_exists($client_name)) {
				throw new Exception ('Client does not exist!'. PHP_EOL);
			}

			$this->load->library('image_lib');

			$client_dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), true) . $client_name, true);
			$client_files = get_dir_file_info($client_dir);

			if(count($client_files) == 0) {
				throw new Exception ('No files!'. PHP_EOL);
			}

			echo "Client upload directory: {$client_dir}" . PHP_EOL;

//			echo "Are you sure you want to do this?  Type 'yes' to continue: ". PHP_EOL;
//			$handle = fopen ("php://stdin","r");
//			$line = fgets($handle);
//			if(trim($line) != 'yes'){
//				echo "ABORTING!\n";
//				exit;
//			}
//			fclose($handle);
//			echo "Thank you, continuing...". PHP_EOL;

			foreach ($client_files as $key => $value)
			{
				//get only project subfolders
				if (is_dir($value["server_path"])) {
					echo 'PROJECT: ' . $value['name'] . PHP_EOL;
					if (empty($subfolder)) {
						$project_dir = set_realpath($value["server_path"], false);
					} else {
						$project_dir = set_realpath($value["server_path"] . DIRECTORY_SEPARATOR . $subfolder, false);
					}
					if (!is_dir($project_dir)) {
						continue;
					}
					$project_files = get_dir_file_info($project_dir);
					foreach ($project_files as $key => $value) {
						if (is_dir($value["server_path"])) {
							continue;
						}
						$file = $value['name'];
						$type = get_mime_by_extension($file);

						if (strpos($type, 'image') !== FALSE) {
							//echo $file . PHP_EOL;
							$thumb_dir = set_realpath($project_dir . 'thumb' . DIRECTORY_SEPARATOR, false);
							if(!file_exists($thumb_dir . $file)) {
								if(!self::imageResize($project_dir, $file)) {
									echo 'Problem with file: ' . $file . PHP_EOL . $this->image_lib->display_errors();
								} else {
									echo $thumb_dir . $file . PHP_EOL;
								}
							}
						}
					}
				}
			}




		} catch (Exception $e) {
			$this->output
				->set_content_type('text/plain')
				->set_status_header(500)
				->set_output($e->getMessage());
		}
	}

	/*
	 * Copied from projects controller
	 */
	private function imageExtract($path, $sec)
	{
		if (false === $exifData = @exif_read_data($path, $sec, false, false)) {
			try {
				if (false === $exifData = @exif_read_data($path, $sec, false, false)) {
					return array();
				}
			} catch (\Exception $e) {
				return array();
			}
		}

		return exif_read_data($path, $sec, false, false);
	}

	private function imageResize($dir, $fn)
	{

		try {
			if (!file_exists($dir . 'thumb')) {
				mkdir($dir . 'thumb', 0777, true);
			}

			$config = array();
			$config['image_library'] = 'gd2';
			$config['source_image'] = $dir . $fn;
			$config['new_image'] = $dir . 'thumb' . DIRECTORY_SEPARATOR;    //only have to specify new folder
			$config['maintain_ratio'] = TRUE;
			$config['master_dim'] = 'width';

			$config['width'] = 300;

			$this->image_lib->clear();
			$this->image_lib->initialize($config);

			if (!$this->image_lib->resize()) {
				return false;
			} else {
				$imgdata = self::imageExtract($config['source_image'], 'IFD0');

				$this->image_lib->clear();
				$config2 = array();

				$config2['image_library'] = 'gd2';
				$config2['source_image'] = $dir . 'thumb' . DIRECTORY_SEPARATOR . $fn;

				if (array_key_exists('Orientation', $imgdata)) {
					switch ($imgdata['Orientation']) {
						case 2:
							//mirror?
							break;
						case 3:
							$config2['rotation_angle'] = '180';
							break;
						case 4:
							$config2['rotation_angle'] = '180';
							//mirror?
							break;
						case 5:
							$config2['rotation_angle'] = '270';
							//mirror?
							break;
						case 6:
							$config2['rotation_angle'] = '270';
							break;
						case 7:
							$config2['rotation_angle'] = '90';
							//mirror?
							break;
						case 8:
							$config2['rotation_angle'] = '90';
							break;
					}
				}

				$this->image_lib->initialize($config2);
				if (!empty($config2['rotation_angle'])) {
					if (!$this->image_lib->rotate()) {
						return false;
					}
				}
			}
			return true;
		}
		catch (Exception $e){
			return false;
		}
	}
}
