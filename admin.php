<?php
$domain = '';
$config = '/var/imap-php/config.yml';
$data = yaml_parse(file_get_contents($config));
$folder_data = $data['folders'][$domain];

if (array_key_exists('action', $_GET))
{
	$action = $_GET['action'];
	switch ($action)
	{
		case "remove_item";
			$folder = $_GET['folder'];
			$item = $_GET['item'];
			if (array_key_exists($folder, $folder_data))
			{
				if (in_array($item, $folder_data[$folder]))
				{
					$new_item_list = [];
					$item_list = $folder_data[$folder];
					foreach ($item_list as $item_name)
					{
						if ($item_name != $item)
						{
							$new_item_list[] = $item_name;
						}
					}
					sort($new_item_list);
					$folder_data[$folder] = $new_item_list;

					if (count($new_item_list) == 0)
					{
						// remove the folder
						$new_folder_data = [];
						$folder_list = $folder_data;
						foreach ($folder_list as $folder_name => $folder_values)
						{
							if ($folder_name != $folder)
							{
								$new_folder_data[$folder_name] = $folder_values;
							}
						}
						$folder_data = $new_folder_data;
					}
					$data['folders'][$domain] = $folder_data;
					file_put_contents($config, yaml_emit($data));
				}
			}
			break;
	}	
	header("Location: " . $_SERVER['PHP_SELF'], 301);
	die();
}

if (array_key_exists('action', $_POST))
{
	$action = $_POST['action'];
	switch ($action)
	{
		case "add_folder_item";
			$folder = $_POST['folder'];
			$item = $_POST['item'];
			if (!array_key_exists($folder, $folder_data))
			{
				$folder_data[$folder] = [];
			}
			$folder_data[$folder][] = $item;
			sort($folder_data[$folder]);
			$data['folders'][$domain] = $folder_data;
			file_put_contents($config, yaml_emit($data));
			break;
		case "add_item_to_folder";
			$folder = $_POST['folder'];
			$item = $_POST['item'];
			if (!array_key_exists($folder, $folder_data))
			{
				$folder_data[$folder] = [];
			}
			$folder_data[$folder][] = $item;
			sort($folder_data[$folder]);
			$data['folders'][$domain] = $folder_data;
			file_put_contents($config, yaml_emit($data));
			break;
	}
	header("Location: " . $_SERVER['PHP_SELF'], 301);
	die();
}

?><!DOCTYPE html>
<html>
	<head>
		<title><?php echo $domain; ?> mail filtering</title>
		<style>
.left, .right {
	width: 50%;
	float: left;
}
.folder:hover {
	cursor: pointer;
	background-color: rgb(234, 234, 234);
}
.item {
	padding-left: 16px;
}
.item:hover {
	cursor: pointer;
	background-color: rgb(234, 234, 0);
}
		</style>
	</head>
	<body>
		<div class="left">
<div class="add-folder-item">
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="action" value="add_folder_item" />
	<div>Add new folder &amp; item:</div>
	<div>folder: <input type="text" name="folder" value="" /></div>
	<div>item: <input type="text" name="item" value="" /></div>
	<div><input type="submit" value="Add" /></div>
	</form>
</div>

<hr />
<div class="add-item-folder">
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="action" value="add_item_to_folder" />
	<div>Add item to folder:</div>
	<div>folder: <select name="folder"><?php
foreach ($folder_data as $folder => $lists)
{
	echo '<option>' . $folder . '</option>';
}
	?></select></div>
	<div>item: <input type="text" name="item" value="" /></div>
	<div><input type="submit" value="Add" /></div>
	</form>
</div>
<hr />

		</div>
		<div class="right">
			Folder and Items<hr />
<?php
foreach ($folder_data as $folder => $lists)
{
	echo '<div class="folder">' . $folder . '</div>';
	foreach ($lists as $item)
	{
		echo '<div class="item">' . $item . ' [<a href="?action=remove_item&folder=' . $folder . '&item=' . $item. '" title="delete">X</a>]</div>';
	}
}
?>
		</div>
	</body>
</html>
