<?php
###########################################################################
#                                                                         #
#  SolusVMController                                                      #
#                                                                         #
#  Copyright (C) 2010  Sei Kan                                            #
#                                                                         #
#  This program is free software: you can redistribute it and/or modify   #
#  it under the terms of the GNU General Public License as published by   #
#  the Free Software Foundation, either version 3 of the License, or      #
#  (at your option) any later version.                                    #
#                                                                         #
#  This program is distributed in the hope that it will be useful,        #
#  but WITHOUT ANY WARRANTY; without even the implied warranty of         #
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          #
#  GNU General Public License for more details.                           #
#                                                                         #
#  You should have received a copy of the GNU General Public License      #
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.  #
#                                                                         #
###########################################################################

defined('INDEX') or die('Access is denied.');
if(!isset($_SESSION['user_id'])){
	header('Location: ?q=login');
	exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : 0;
$grp = isset($_GET['grp']) ? $_GET['grp'] : 0;
$pvdr = isset($_GET['pvdr']) ? $_GET['pvdr'] : 0;
$referer = isset($_GET['ref']) ? $_GET['ref'] : '?q=vps';
$pageLink = urlencode(preg_replace('/&ref=[^$]+$/', '', getPageURL()));

$output = '<div id="main">';

switch($action){
	case 'add':
		$title = ADD_VPS;
		$includeJS[] = 'includes/js/calendarview.js';
		$scripts = 'Event.observe(window, \'load\', function() { Calendar.setup({dateField:\'dueDate\',triggerElement:\'calendar\'}); })';

		$status = '';
		$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
		$key = isset($_POST['key']) ? htmlspecialchars(strtoupper($_POST['key'])) : '';
		$hash = isset($_POST['hash']) ? htmlspecialchars(strtolower($_POST['hash'])) : '';
		$protocol = isset($_POST['protocol']) ? $_POST['protocol'] : 'http';
		$protocol = ($protocol == 'https') ? 'https' : 'http';
		$host = isset($_POST['host']) ? htmlspecialchars(strtolower($_POST['host'])) : '';
		$port = isset($_POST['port']) ? $_POST['port'] : '';
		$groupId = isset($_POST['group']) ? $_POST['group'] : 0;
		$providerId = isset($_POST['provider']) ? $_POST['provider'] : 0;
		$country = isset($_POST['country']) ? $_POST['country'] : '';
		$location = isset($_POST['location']) ? $_POST['location'] : '';
		$currency = isset($_POST['currency']) ? $_POST['currency'] : 'USD';
		$price = isset($_POST['price']) ? $_POST['price'] : 0;
		$period = isset($_POST['period']) ? $_POST['period'] : '';
		$dueDate = isset($_POST['dueDate']) ? $_POST['dueDate'] : date('Y-m-d', getTimestamp('', $_SESSION['time_zone'], $_SESSION['dst'])+(60*60*24*365));

		$notes = isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';

		$protocols = array('http'=>'http://', 'https'=>'https://');

		$protocolOptions = '<select name="protocol" id="protocol">';
		foreach($protocols as $optionKey=>$optionValue){
			$protocolOptions .= '<option value="' . $optionKey . '"' . ($optionKey==$protocol ? ' selected' : '') . '> ' . $optionValue . '</option>';
		}
		$protocolOptions .= '</select>';

		$groupOptions = '<select name="group" id="group">
		<option value="0"> -</option>';

		$group = new csvHandler(TABLES . SVMC_CODE . 'group.tab', '|', 'group_id');
		$result = $group->select();

		if($result){
			foreach($result as $r){
				$groupOptions .= '	<option value="' . $r['group_id'] . '"' . ($r['group_id'] == $groupId ? ' selected' : '') . '> ' . $r['group_name'] . '</option>';
			}
		}
		$groupOptions .= '</select>';

		$providerOptions = '<select name="provider" id="provider">
		<option value="0"> -</option>';

		$provider = new csvHandler(TABLES . SVMC_CODE . 'provider.tab', '|', 'provider_id');
		$result = $provider->select();

		if($result){
			foreach($result as $r){
				$providerOptions .= '	<option value="' . $r['provider_id'] . '"' . ($r['provider_id'] == $providerId ? ' selected' : '') . '> ' . $r['provider_name'] . '</option>';
			}
		}
		$providerOptions .= '</select>';

		$countryOptions = '<select name="country" id="country">
			<option value="-"> -</>';
		foreach($countryList as $countryCode=>$countryName){
			$countryOptions .= '	<option value="' . $countryCode . '"' . ($countryCode==$country ? ' selected' : '') . '> ' . $countryName . '</option>';
		}
		$countryOptions .= '</select>';

		$currencyOptions = '<select name="currency" id="currency">
			<option value="-"> -</option>';
		foreach($currencyList as $currencyCode=>$currencySign){
			$currencyOptions .= '	<option value="' . $currencyCode . '"' . ($currencyCode==$currency ? ' selected' : '') . '> ' . $currencyCode . '</option>';
		}

		$periodOptions = '<select name="period" id="period">
			<option value="-"> -</option>';

		foreach($periodList as $periodKey=>$periodValue){
			$periodOptions .= '	<option value="' . $periodKey . '"' . ($periodKey==$period ? ' selected' : '') . '> ' . $periodValue . '</option>';
		}
		$periodOptions .= '</select>';

		if(isset($_POST['name'])){
			if(empty($name)){
				$status .= '<p class="red">' . VPS_NAME_CANNOT_BE_BLANK .'</p>';
			}
			if(strlen($name) > 50){
				$status .= '<p class="red">' . VPS_NAME_CANNOT_EXCEED_50_CHARACTERS . '</p>';
			}
			if(!preg_match('/^[0-9A-Z]{5}\-[0-9A-Z]{5}\-[0-9A-Z]{5}$/', $key)){
				$status .= '<p class="red">' . A_VALID_API_KEY_SHOULD_LOOK_LIKE . '</p>';
			}
			if(!preg_match('/^[a-z0-9]{40}$/', $hash)){
				$status .= '<p class="red">' . A_VALID_API_HASH_SHOULD_BE_40_CHARACTERS_IN_LENGTH . '</p>';
			}
			if(!preg_match('/^([a-z0-9\-]+\.)?[a-z0-9\-]+\.[a-z]{2,4}|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $host)){
				$status .= '<p class="red">' . YOU_MUST_PROVIDE_A_VALID_HOST . '</p>';
			}
			if(!empty($port) && !preg_match('/[0-9]+/', $port)){
				$status .= '<p class="red">' . str_replace('%port%', $port, IS_NOT_A_VALID_PORT) . '</p>';
			}
			if(strlen($location) > 50){
				$status .= '<p class="red">' . LOCATION_CANNOT_EXCEED_50_CHARACTERS . '</p>';
			}
			if(!empty($price) && !preg_match('/^[0-9.]+$/is', $price)){
				$status .= '<p class="red">' . str_replace('%price%', htmlspecialchars($price), IS_NOT_A_VALID_PRICE) . '</p>';
			}
			if(!empty($dueDate)){
				if(preg_match('/^(20[0-9]{2})-([0-9]{1,2})-([0-9]{1,2})$/', $dueDate, $matches)){
					if(!checkdate($matches[2], $matches[3], $matches[1])){
						$status .= '<p class="red">' . str_replace('%date%', htmlspecialchars($dueDate), IS_NOT_A_VALID_DATE) . '</p>';
					}
				}
				else{
					$status .= '<p class="red">' . str_replace('%date%', htmlspecialchars($dueDate), IS_NOT_A_VALID_DATE) . '</p>';
				}
			}
			if(strlen($notes) > 200){
				$status .= '<p class="red">' . NOTES_CANNOT_EXCEED_200_CHARACTERS . '</p>';
			}

			if(empty($status)){
				$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');

				$result = $vps->select('vps_name', $name);

				if($result) $status = '<p class="red">' . str_replace('%name%', $name, VPS_NAME_ALREADY_EXISTED) . '</p>';
			}

			if(empty($status)){
				$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
				$vps->add(array('vps_id'=>$vps->getLastId()+1, 'vps_name'=>$name, 'key'=>$key, 'hash'=>$hash, 'protocol'=>$protocol, 'host'=>$host, 'port'=>$port, 'country'=>$country, 'location'=>$location, 'price'=>$price, 'currency'=>$currency, 'period'=>$period, 'due_date'=>getTimestamp(strtotime($dueDate . ' 00:00:00'), $_SESSION['time_zone'], $_SESSION['dst']), 'group_id'=>$groupId, 'provider_id'=>$providerId, 'notes'=>str_replace(array("\n", '|'), array('\n', '<pipe>'), $notes)));
				$_SESSION['status'] = '<p class="green">' . str_replace('%name%', $name, VPS_HAS_BEEN_ADDED) . '</p>';
				header('Location: ?q=vps');
				exit;
			}
		}

		$output .= '<h1>' . ADD_VPS . '</h1>
		<form action="?action=add" method="post">
			' . $status . '
			<label for="name">' . VPS_NAME . '</label>
			<input type="text" name="name" id="name" value="' . $name . '" maxlength="50" size="64" class="text" style="width:486px;" /> <span class="red">*</span>
			<div id="horizontal">
				<ul>
					<li>
						<label for="key">' . API_KEY . '</label>
						<input type="text" name="key" id="key" value="' . $key . '" maxlength="17" size="64" class="text" style="width:150px;" /> <span class="red">*</span>
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="hash">' . API_HASH . '</label>
						<input type="text" name="hash" id="hash" value="' . $hash . '" maxlength="40" size="64" class="text" style="width:280px;" /> <span class="red">*</span>
					</li>
				</ul>
			</div>
			<div class="clear">&nbsp;</div>
			<div id="horizontal">
				<ul>
					<li>
						<label for="host">' . HOST . '</label>
						' . $protocolOptions . ' <input type="text" name="host" id="host" value="' . $host . '" maxlength="50" size="50" class="text" style="width:315px;" /> <span class="red">*</span>
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="port">' . PORT . '</label>
						<input type="text" name="port" id="port" value="' . $port . '" maxlength="5" size="10" class="text" style="width:40px;" />
					</li>
				</ul>
			</div>
			<div class="clear">&nbsp;</div>
			<div id="horizontal">
				<ul>
					<li>
						<label for="group">' . GROUP . '</label>
						' . $groupOptions . '
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="provider">' . PROVIDER . '</label>
						' . $providerOptions . '
					</li>
				</ul>
			</div>
			<div class="clear">&nbsp;</div>
			<div id="horizontal">
				<ul>
					<li>
						<label for="country">' . COUNTRY . '</label>
						' . $countryOptions . '
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="location">' . LOCATION . '</label>
						<input type="text" name="location" id="location" value="' . $location . '" maxlength="50" size="64" class="text" style="width:159px;" />
					</li>
				</ul>
			</div>
			<div class="clear">&nbsp;</div>
			<div id="horizontal">
				<ul>
					<li>
						<label for="price">' . PRICE . '</label>
						' . $currencyOptions . ' <input type="text" name="price" id="price" value="' . $price . '" maxlength="6" size="10" class="text" style="width:60px;" />
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="period">' . PERIOD . '</label>
					' . $periodOptions . '
					</li>
					<li>&nbsp;</li>
					<li>
						<label for="dueDate">' . DUE_DATE . '</label>
						<input type="text" name="dueDate" id="dueDate" value="' . $dueDate . '" maxlength="10" size="15" readonly class="text" style="width:70px;" />
						<a href="javascript:;" id="calendar"><img src="images/icons/calendar.png" border="0" width="16" height="16" align="absMiddle" /></a>
					</li>
				</ul>
			</div>
			<div class="clear">&nbsp;</div>
			<label for="notes">' . NOTES . '</label>
			<textarea name="notes" id="notes">' . $notes . '</textarea>
			<p>&nbsp;</p>
			<input class="button" type="submit" value="' . ADD_VPS . '" /> <a href="' . $referer . '">' . CANCEL . '</a>
		</form>';
	break;

	case 'view':
		$title = VPS_INFORMATION;
		$output .= '<h1>' . VPS_INFORMATION . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			$solus = new SolusVM;
			$solus->setKey($result[0]['key']);
			$solus->setHash($result[0]['hash']);
			$solus->setProtocol($result[0]['protocol']);
			$solus->setHost($result[0]['host']);
			if($result[0]['port']) $solus->setPort($result[0]['port']);
			$data = $solus->getStatus();

			if($data){
				$group = new csvHandler(TABLES . SVMC_CODE . 'group.tab', '|', 'group_id');
				$groupResult = $group->select('group_id', $result[0]['group_id']);
				$groupName = ($groupResult) ? $groupResult[0]['group_name'] : '-';

				$provider = new csvHandler(TABLES . SVMC_CODE . 'provider.tab', '|', 'provider_id');
				$providerResult = $provider->select('provider_id', $result[0]['provider_id']);
				$providerName = ($providerResult) ? $providerResult[0]['provider_name'] : '-';

				$location = (isset($countryList[$result[0]['country']])) ? '<img src="images/flags/' . strtolower($result[0]['country']) . '.png" border="0" align="absMiddle" /> ' . $countryList[$result[0]['country']] : '-';
				$location .= (!empty($result[0]['location'])) ? ' (' . $result[0]['location'] . ')' : '';

				$price = $result[0]['currency'] . ' ' . $currencyList[$result[0]['currency']];
				$price .= (!empty($result[0]['price'])) ? number_format($result[0]['price'], 2) : '-';

				$date = ($result[0]['due_date']-time() < 0) ? '<span class="red">' . date('d M, Y', getTimestamp($result[0]['due_date'], $_SESSION['time_zone'], $_SESSION['dst'])) . '</span>' : date('d M, Y', getTimestamp($result[0]['due_date'], $_SESSION['time_zone'], $_SESSION['dst']));

					$paymentStatus = ($result[0]['due_date']-time() < 0) ? '<img src="images/icons/coins_disable.png" width="16" height="16" border="0" alt="'. SUBSCRIPTION_DUED . '" title="' . SUBSCRIPTION_DUED . '" align="absMiddle" />' : (($result[0]['due_date']-time() < 604800) ? '<img src="images/icons/coins_error.png" width="16" height="16" border="0" alt="'. SUBSCRIPTION_DUE_WITHIN_7_DAYS . '" title="' . SUBSCRIPTION_DUE_WITHIN_7_DAYS . '" align="absMiddle" />' : '<br />' . str_replace('%day%', (floor(($result[0]['due_date']-time())/86400)), EXPIRING_IN_DAY));

				$_SESSION['vps_' . $id] = ($data['statusmsg'] == 'online') ? '<img src="images/icons/connect_online.png" border="0" alt="' . ONLINE . '" title="' . ONLINE . '" align="absMiddle" /> ' . ONLINE : (($data['statusmsg'] == 'offline') ? '<img src="images/icons/connect_offline.png" border="0" alt="' . OFFLINE . '" title="' . OFFLINE . '" align="absMiddle" /> ' . OFFLINE : '<img src="images/icons/connect_error.png" border="0" width="16" height="16" alt="' . ERROR . '" title="' . ERROR . '" align="absMiddle" /> ' . ERROR);

				if(isset($_SESSION['status'])){
					$output .= $_SESSION['status'];
					unset($_SESSION['status']);
				}

				$output .= '
				<fieldset>
					<legend><img src="images/icons/computer.png" border="0" alt="' . $result[0]['vps_name'] . '" title="' . $result[0]['vps_name'] . '" align="absMiddle" /> ' . $result[0]['vps_name'] . '</legend>
					<div id="horizontal">
						<ul>
							<li>
								<label>' . HOSTNAME . '</label>
								' . $data['hostname'] . '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . STATUS . '</label>
								' . $_SESSION['vps_' . $id] . '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . GROUP . '</label>
								' . $groupName . '
							</li>
						</ul>
					</div>
					<div class="clear">&nbsp;</div>

					<div id="horizontal">
						<ul>
							<li>
								<label>' . IP_ADDRESS . '</label>
								' . $data['ipaddress'] . '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . LOCATION . '</label>
								' . $location . '
							</li>
						</ul>
					</div>
					<div class="clear">&nbsp;</div>
					<div id="horizontal">
						<ul>
							<li>
								<label>' . PROVIDER . '</label>
								' . $providerName;

								if(isset($providerResult[0]['website_url'])){
									$output .= '<div>';

									if(preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $providerResult[0]['website_url'])){
										$output .= '<a href="' . $providerResult[0]['website_url'] . '" target="blank"><img src="images/icons/link.png" width="16" height="16" border="0" alt="' . WEBSITE . '" title="' . WEBSITE . '" align="absMiddle" /></a>';
									}

									if(preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $providerResult[0]['support_url'])){
										$output .= '&nbsp;&nbsp;<a href="' . $providerResult[0]['support_url'] . '" target="blank"><img src="images/icons/lifebuoy.png" width="16" height="16" border="0" alt="' . SUPPORT_PAGE . '" title="' . SUPPORT_PAGE . '" align="absMiddle" /></a>';
									}

									$output .= '</div>';
								}

					$output .= '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . PRICE . '</label>
								' . $price . '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . PERIOD . '</label>
								' . $periodList[$result[0]['period']] . '
							</li>
							<li>&nbsp;</li>
							<li>
								<label>' . DUE_DATE . '</label>
								' . $date  . ' ' . $paymentStatus .
							'</li>
						</ul>
					</div>
					<div class="clear">&nbsp;</div>
					<label>' . NOTES . '</label>
					' . (empty($result[0]['notes']) ? '-' : str_replace(array('\n', '<pipe>'), array('<br />', '|'), $result[0]['notes'])) . '
					<p class="separator">
						<div style="float:left;">
							<a href="?action=boot&id=' . $result[0]['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_start.png" border="0" alt="' . BOOT . '" title="' . BOOT . '" align="absMiddle" /> ' . BOOT . '</a> |
							<a href="?action=shutdown&id=' . $result[0]['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_stop.png" border="0" alt="' . SHUTDOWN . '" title="' . SHUTDOWN . '" align="absMiddle" /> ' . SHUTDOWN . '</a> |
							<a href="?action=reboot&id=' . $result[0]['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_restart.png" border="0" alt="' . REBOOT . '" title="' . REBOOT . '" align="absMiddle" /> ' . REBOOT . '</a>
						</div>
						<div style="float:right">
							<a href="?action=edit&id=' . $result[0]['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/page_white_edit.png" border="0" alt="' . EDIT . '" title="' . EDIT . '" align="absMiddle" /> ' . EDIT . '</a> |
							<a href="?action=remove&id=' . $result[0]['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/bin.png" border="0" alt="' . REMOVE . '" title="' . REMOVE . '" align="absMiddle" /> ' . REMOVE . '</a> |
							<a href="' . $referer . '">' . BACK . '</a>
						</div>
					</p>
					<div class="clear"></div>
				</fieldset>';
			}
			else{
				$output .= '<p class="red">' . ERROR_RETRIEVING_VPS_STATUS . '</p>
				<p><input class="button" type="button" value="' . BACK . '" onclick="window.location.href=\'' . $referer . '\';" /></p>';
			}
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	case 'boot':
		$title = BOOT;

		$output .= '<h1>' . BOOT . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			$output .= '<form method="post">
				<input type="hidden" name="back" />';

			$solus = new SolusVM;
			$solus->setKey($result[0]['key']);
			$solus->setHash($result[0]['hash']);
			$solus->setProtocol($result[0]['protocol']);
			$solus->setHost($result[0]['host']);
			if($result[0]['port']) $solus->setPort($result[0]['port']);
			$data = $solus->boot();

			if($data){
				if($data['statusmsg'] == 'booted'){
					$output .= '<p class="green">' . str_replace('%name%', $result[0]['vps_name'], VPS_IS_BOOTED) . '</p>';
				}
				else{
					$output .= '<p class="red">' . str_replace('%name%', $result[0]['vps_name'], FAILED_TO_BOOT_VPS) . '</p>';
				}
			}
			else{
				$output .= '<p class="red">' . ERROR_RETRIEVING_VPS_STATUS . '</p>';
			}

			$output .= '<p><input class="button" type="button" value="' . BACK . '" onclick="window.location.href=\'' . $referer . '\';" /></p>
				</form>';
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	case 'shutdown':
		$title = SHUTDOWN;

		$output .= '<h1>' . SHUTDOWN . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			$output .= '<form method="post">
				<input type="hidden" name="back" />';

			$solus = new SolusVM;
			$solus->setKey($result[0]['key']);
			$solus->setHash($result[0]['hash']);
			$solus->setProtocol($result[0]['protocol']);
			$solus->setHost($result[0]['host']);
			if($result[0]['port']) $solus->setPort($result[0]['port']);
			$data = $solus->shutdown();

			if($data){
				if($data['statusmsg'] == 'shutdown'){
					$output .= '<p class="green">' . str_replace('%name%', $result[0]['vps_name'], VPS_IS_SHUTTED_DOWN) . '</p>';
				}
				else{
					$output .= '<p class="red">' . str_replace('%name%', $result[0]['vps_name'], FAILED_TO_SHUTDOWN_VPS) . '</p>';
				}
			}
			else{
				$output .= '<p class="red">' . ERROR_RETRIEVING_VPS_STATUS . '</p>';
			}

			$output .= '<p><input class="button" type="button" value="' . BACK . '" onclick="window.location.href=\'' . $referer . '\';" /></p>
				</form>';
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	case 'reboot':
		$title = REBOOT;

		$output .= '<h1>Reboot</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			$output .= '<form method="post">
				<input type="hidden" name="back" />';

			$solus = new SolusVM;
			$solus->setKey($result[0]['key']);
			$solus->setHash($result[0]['hash']);
			$solus->setProtocol($result[0]['protocol']);
			$solus->setHost($result[0]['host']);
			if($result[0]['port']) $solus->setPort($result[0]['port']);
			$data = $solus->reboot();

			if($data){
				if($data['statusmsg'] == 'rebooted'){
					$output .= '<p class="green">' . str_replace('%name%', $result[0]['vps_name'], VPS_IS_REBOOTED) . '</p>';
				}
				else{
					$output .= '<p class="red">' . str_replace('%name%', $result[0]['vps_name'], FAILED_TO_REBOOT_VPS) . '</p>';
				}
			}
			else{
				$output .= '<p class="red">' . ERROR_RETRIEVING_VPS_STATUS . '</p>';
			}

			$output .= '<p><input class="button" type="button" value="' . BACK . '" onclick="window.location.href=\'' . $referer . '\';" /></p>
				</form>';
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	case 'edit':
		$title = EDIT_VPS;
		$includeJS[] = 'includes/js/calendarview.js';
		$scripts = 'Event.observe(window, \'load\', function() { Calendar.setup({dateField:\'dueDate\',triggerElement:\'calendar\'}); })';

		$output .= '<h1>' . EDIT_VPS . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			$status = '';
			$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : $result[0]['vps_name'];
			$key = isset($_POST['key']) ? htmlspecialchars(strtoupper($_POST['key'])) : $result[0]['key'];
			$hash = isset($_POST['hash']) ? htmlspecialchars(strtolower($_POST['hash'])) : $result[0]['hash'];
			$protocol = isset($_POST['protocol']) ? $_POST['protocol'] : $result[0]['protocol'];
			$protocol = ($protocol == 'https') ? 'https' : 'http';
			$host = isset($_POST['host']) ? htmlspecialchars(strtolower($_POST['host'])) : $result[0]['host'];
			$port = isset($_POST['port']) ? $_POST['port'] : $result[0]['port'];
			$groupId = isset($_POST['group']) ? $_POST['group'] : $result[0]['group_id'];
			$providerId = isset($_POST['provider']) ? $_POST['provider'] : $result[0]['provider_id'];
			$country = isset($_POST['country']) ? $_POST['country'] : $result[0]['country'];
			$location = isset($_POST['location']) ? $_POST['location'] : $result[0]['location'];
			$currency = isset($_POST['currency']) ? $_POST['currency'] : $result[0]['currency'];
			$price = isset($_POST['price']) ? $_POST['price'] : $result[0]['price'];
			$period = isset($_POST['period']) ? $_POST['period'] : $result[0]['period'];
			$dueDate = isset($_POST['dueDate']) ? $_POST['dueDate'] : date('Y-m-d', $result[0]['due_date']);
			$notes = isset($_POST['notes']) ? $_POST['notes'] : str_replace(array('\n', '<pipe>'), array("\n", '|'), $result[0]['notes']);

			$protocols = array('http'=>'http://', 'https'=>'https://');

			$protocolOptions = '<select name="protocol" id="protocol">';
			foreach($protocols as $optionKey=>$optionValue){
				$protocolOptions .= '<option value="' . $optionKey . '"' . ($optionKey==$protocol ? ' selected' : '') . '> ' . $optionValue . '</option>';
			}
			$protocolOptions .= '</select>';

			$groupOptions = '<select name="group" id="group">
			<option value="0"> -</option>';

			$group = new csvHandler(TABLES . SVMC_CODE . 'group.tab', '|', 'group_id');
			$result = $group->select();

			if($result){
				foreach($result as $r){
					$groupOptions .= '	<option value="' . $r['group_id'] . '"' . ($r['group_id'] == $groupId ? ' selected' : '') . '> ' . $r['group_name'] . '</option>';
				}
			}
			$groupOptions .= '</select>';

			$providerOptions = '<select name="provider" id="provider">
			<option value="0"> -</option>';

			$provider = new csvHandler(TABLES . SVMC_CODE . 'provider.tab', '|', 'provider_id');
			$result = $provider->select();

			if($result){
				foreach($result as $r){
					$providerOptions .= '	<option value="' . $r['provider_id'] . '"' . ($r['provider_id'] == $providerId ? ' selected' : '') . '> ' . $r['provider_name'] . '</option>';
				}
			}
			$providerOptions .= '</select>';

			$countryOptions = '<select name="country" id="country">
				<option value="-"> -</>';
			foreach($countryList as $countryCode=>$countryName){
				$countryOptions .= '	<option value="' . $countryCode . '"' . ($countryCode==$country ? ' selected' : '') . '> ' . $countryName . '</option>';
			}
			$countryOptions .= '</select>';

			$currencyOptions = '<select name="currency" id="currency">
				<option value="-"> -</option>';
			foreach($currencyList as $currencyCode=>$currencySign){
				$currencyOptions .= '	<option value="' . $currencyCode . '"' . ($currencyCode==$currency ? ' selected' : '') . '> ' . $currencyCode . '</option>';
			}

			$periodOptions = '<select name="period" id="period">
				<option value="-"> -</option>';

			foreach($periodList as $periodKey=>$periodValue){
				$periodOptions .= '	<option value="' . $periodKey . '"' . ($periodKey==$period ? ' selected' : '') . '> ' . $periodValue . '</option>';
			}
			$periodOptions .= '</select>';

			if(isset($_POST['name'])){
				if(empty($name)){
					$status .= '<p class="red">' . VPS_NAME_CANNOT_BE_BLANK .'</p>';
				}
				if(strlen($name) > 50){
					$status .= '<p class="red">' . VPS_NAME_CANNOT_EXCEED_50_CHARACTERS . '</p>';
				}
				if(!preg_match('/^[0-9A-Z]{5}\-[0-9A-Z]{5}\-[0-9A-Z]{5}$/', $key)){
					$status .= '<p class="red">' . A_VALID_API_KEY_SHOULD_LOOK_LIKE . '</p>';
				}
				if(!preg_match('/^[a-z0-9]{40}$/', $hash)){
					$status .= '<p class="red">' . A_VALID_API_HASH_SHOULD_BE_40_CHARACTERS_IN_LENGTH . '</p>';
				}
				if(!preg_match('/^([a-z0-9\-]+\.)?[a-z0-9\-]+\.[a-z]{2,4}|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $host)){
					$status .= '<p class="red">' . YOU_MUST_PROVIDE_A_VALID_HOST . '</p>';
				}
				if(!empty($port) && !preg_match('/[0-9]+/', $port)){
					$status .= '<p class="red">' . str_replace('%port%', $port, IS_NOT_A_VALID_PORT) . '</p>';
				}
				if(strlen($location) > 50){
					$status .= '<p class="red">' . LOCATION_CANNOT_EXCEED_50_CHARACTERS . '</p>';
				}
				if(!empty($price) && !preg_match('/^[0-9.]+$/is', $price)){
					$status .= '<p class="red">' . str_replace('%price%', htmlspecialchars($price), IS_NOT_A_VALID_PRICE) . '</p>';
				}
				if(!empty($dueDate)){
					if(preg_match('/^(20[0-9]{2})-([0-9]{1,2})-([0-9]{1,2})$/', $dueDate, $matches)){
						if(!checkdate($matches[2], $matches[3], $matches[1])){
							$status .= '<p class="red">' . str_replace('%date%', htmlspecialchars($dueDate), IS_NOT_A_VALID_DATE) . '</p>';
						}
					}
					else{
						$status .= '<p class="red">' . str_replace('%date%', htmlspecialchars($dueDate), IS_NOT_A_VALID_DATE) . '</p>';
					}
				}
				if(strlen($notes) > 200){
					$status .= '<p class="red">' . NOTES_CANNOT_EXCEED_200_CHARACTERS . '</p>';
				}

				if(empty($status)){
					$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');

					$result = $vps->select('vps_name', $name);

					if($result && $result[0]['vps_id'] != $id) $status = '<p class="red">' . str_replace('%name%', $name, VPS_NAME_ALREADY_EXISTED) . '</p>';
				}

				if(empty($status)){
					$vps->update($id, array('vps_name'=>$name, 'key'=>$key, 'hash'=>$hash, 'protocol'=>$protocol, 'host'=>$host, 'port'=>$port, 'country'=>$country, 'location'=>$location, 'price'=>$price, 'currency'=>$currency, 'period'=>$period, 'due_date'=>getTimestamp(strtotime($dueDate . ' 00:00:00'), $_SESSION['time_zone'], $_SESSION['dst']), 'group_id'=>$groupId, 'provider_id'=>$providerId, 'notes'=>str_replace(array("\n", '|'), array('\n', '<pipe>'), $notes)));
					$_SESSION['status'] = '<p class="green">' . str_replace('%name%', $name, VPS_HAS_BEEN_UPDATED) . '</p>';

					header('Location: ' . $referer);
					exit;
				}
			}

			$output .= '<form action="?action=edit&id=' . $id . '&ref=' . urlencode($referer) . '" method="post">
				' . $status . '
				<label for="name">' . VPS_NAME . '</label>
				<input type="text" name="name" id="name" value="' . $name . '" maxlength="50" size="64" class="text" style="width:486px;" /> <span class="red">*</span>
				<div id="horizontal">
					<ul>
						<li>
							<label for="key">' . API_KEY . '</label>
							<input type="text" name="key" id="key" value="' . $key . '" maxlength="17" size="64" class="text" style="width:150px;" /> <span class="red">*</span>
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="hash">' . API_HASH . '</label>
							<input type="text" name="hash" id="hash" value="' . $hash . '" maxlength="40" size="64" class="text" style="width:280px;" /> <span class="red">*</span>
						</li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				<div id="horizontal">
					<ul>
						<li>
							<label for="host">' . HOST . '</label>
							' . $protocolOptions . ' <input type="text" name="host" id="host" value="' . $host . '" maxlength="50" size="50" class="text" style="width:315px;" /> <span class="red">*</span>
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="port">' . PORT . '</label>
							<input type="text" name="port" id="port" value="' . $port . '" maxlength="5" size="10" class="text" style="width:40px;" />
						</li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				<div id="horizontal">
					<ul>
						<li>
							<label for="group">' . GROUP . '</label>
							' . $groupOptions . '
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="provider">' . PROVIDER . '</label>
							' . $providerOptions . '
						</li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				<div id="horizontal">
					<ul>
						<li>
							<label for="country">' . COUNTRY . '</label>
							' . $countryOptions . '
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="location">' . LOCATION . '</label>
							<input type="text" name="location" id="location" value="' . $location . '" maxlength="50" size="64" class="text" style="width:159px;" />
						</li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				<div id="horizontal">
					<ul>
						<li>
							<label for="price">' . PRICE . '</label>
							' . $currencyOptions . ' <input type="text" name="price" id="price" value="' . $price . '" maxlength="6" size="10" class="text" style="width:60px;" />
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="period">' . PERIOD . '</label>
						' . $periodOptions . '
						</li>
						<li>&nbsp;</li>
						<li>
							<label for="dueDate">' . DUE_DATE . '</label>
							<input type="text" name="dueDate" id="dueDate" value="' . $dueDate . '" maxlength="10" size="15" readonly class="text" style="width:70px;" />
							<a href="javascript:;" id="calendar"><img src="images/icons/calendar.png" border="0" width="16" height="16" align="absMiddle" /></a>
						</li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				<label for="notes">' . NOTES . '</label>
				<textarea name="notes" id="notes">' . $notes . '</textarea>
				<p>&nbsp;</p>
				<input class="button" type="submit" value="' . UPDATE . '" /> <a href="' . $referer . '">' . CANCEL . '</a>
			</form>';
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	case 'remove':
		$title = REMOVE_VPS;

		$output .= '<h1>' . REMOVE_VPS . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');
		$result = $vps->select('vps_id', $id);

		if($result){
			if(isset($_POST['remove'])){
				$vps->delete($id);
				$_SESSION['status'] = '<p class="green">' . str_replace('%name%', $result[0]['vps_name'], VPS_HAS_BEEN_REMOVED) . '</p>';
				unset($_SESSION['vps_' . $id]);
				header('Location: ?q=vps');
				exit;
			}

			$output .= '<form action="?action=remove&id=' . $id . '" method="post">
				<input type="hidden" name="remove" />
				' . str_replace('%name%', $result[0]['vps_name'], CONFIRM_TO_REMOVE_VPS) . '
				<p>&nbsp;</p>
				<input class="button" type="submit" value="' . REMOVE . '" /> <a href="' . $referer . '">' . CANCEL . '</a>
			</form>';
		}
		else{
			$output .= '<p class="red">' . str_replace('%id%', htmlspecialchars($id), NO_VPS_IS_FOUND_WITH_ID) . '</p>';
		}
	break;

	default:
		$title = VPS_LIST;

		$scripts = '
// preload images
(new Image()).src=\'images/icons/loading.gif\';
(new Image()).src=\'images/icons/connect_error.png\';
(new Image()).src=\'images/icons/connect_offline.png\';
(new Image()).src=\'images/icons/connect_online.png\';
(new Image()).src=\'images/icons/connect_unknown.png\';

function getStatus(){
	var rows = $A(document.getElementsByTagName(\'div\'));

	rows.each(
		function (div){
			var id = div.id;

			if(id.startsWith(\'status_\')){
				$(id).update(\'<img src="images/icons/loading.gif" border="0" width="16" height="11" align="absMiddle" />\');
			}
		}
	);

	rows.each(
		function (div){
			var id = div.id;

			if(id.startsWith(\'status_\')){
				new Ajax.Request(encodeURI(\'?q=ajax.status&id=\' + id.replace(\'status_\', \'\') + \'\'),{
					method:\'get\',
					encoding: \'UTF-8\',
					onSuccess: function(transport){
						var response = transport.responseText;
						$(id).update(response);
					},
					onFailure: function(){ alert(\'Connection error,\\nplease try again later.\') }
				});
			}
		}
	);
}
';

		if(!isset($_SESSION['last_check']) || (isset($_SESSION['last_check']) && $_SESSION['last_check'] < (time()-18000))){
			$scripts .= 'Event.observe( window, \'load\', function(){ getStatus(); });';
		}

		$output .= '<h1>' . VPS_LIST . '</h1>';

		$vps = new csvHandler(TABLES . SVMC_CODE . 'vps.tab', '|', 'vps_id');

		$result = $vps->select();

		if($result){
			$output .= '<div id="browse">
				<ul>
					<li><b>' . GROUP . ' </b>
				<select name="group" id="group" onchange="window.location.href=\'?q=vps&grp=\'+$(this).getValue()+\'&pvdr=\'+$(\'provider\').getValue();">
					<option value="0"> ' . ALL . '</option>';

			$group = new csvHandler(TABLES . SVMC_CODE . 'group.tab', '|', 'group_id');
			$groupResult = $group->select();

			if($groupResult && count($groupResult) > 0){
				foreach($groupResult as $r){
					$output .= '	<option value="' . $r['group_id'] . '"' . ($r['group_id'] == $grp ? ' selected' : '') . '> ' . $r['group_name'] . '</option>';
				}
			}

			$output .= '</select>
				</li>
				<li>&nbsp;</li>
				<li><b>' . PROVIDER . ' </b>
				<select name="provider" id="provider" onchange="window.location.href=\'?q=vps&pvdr=\'+$(this).getValue()+\'&grp=\'+$(\'group\').getValue();">
					<option value="0"> ' . ALL . '</option>';

			$provider = new csvHandler(TABLES . SVMC_CODE . 'provider.tab', '|', 'provider_id');
			$providerResult = $provider->select();

			if($providerResult && count($providerResult) > 0){
				foreach($providerResult as $r){
					$output .= '	<option value="' . $r['provider_id'] . '"' . ($r['provider_id'] == $pvdr ? ' selected' : '') . '> ' . $r['provider_name'] . '</option>';
				}
			}

			$output .= '</select>
					</li>
				</ul>
			</div>';

			if(isset($_SESSION['status'])){
				$output .= $_SESSION['status'];
				unset($_SESSION['status']);
			}

			$display = array();
			foreach($result as $r){
				if(($grp == 0 && $pvdr == 0) || ($grp == 0 && $r['provider_id'] == $pvdr) || ($pvdr == 0 && $r['group_id'] == $grp) || ($r['group_id'] == $grp && $r['provider_id'] == $pvdr)) $display[] = $r;
			}

			if(count($display) > 0){
				$output .= '<div class="table">
					<div class="th">
						<div class="td" style="width:3%;">&nbsp;</div>
						<div class="td" style="width:20%;">' . VPS_NAME . '</div>
						<div class="td" style="width:20%;">' . LOCATION . '</div>
						<div class="td" style="width:10%;">' . GROUP . '</div>
						<div class="td" style="width:11%;">' . PROVIDER . '</div>
						<div class="td" style="width:3%;">&nbsp;</div>
						<div class="td" style="width:8%;">' . ACTION . '</div>
						<div class="td" style="width:8%;">&nbsp;</div>
						<div class="clear"></div>
					</div>';

				foreach($display as $r){
					$status = isset($_SESSION['vps_' . $r['vps_id']]) ? $_SESSION['vps_' . $r['vps_id']] : '<img src="images/icons/connect_unknown.png" border="0" alt="' . UNKNOWN . '" title="' . UNKNOWN . '" align="absMiddle" />';

					$groupResult = $group->select('group_id', $r['group_id']);
					$groupName = ($groupResult) ? $groupResult[0]['group_name'] : '-';

					$providerResult = $provider->select('provider_id', $r['provider_id']);
					$providerName = ($providerResult) ? $providerResult[0]['provider_name'] : '-';

					$paymentStatus = (($r['due_date']-time()) < 0) ? '<img src="images/icons/coins_disable.png" width="16" height="16" border="0" alt="'. SUBSCRIPTION_DUED . '" title="' . SUBSCRIPTION_DUED . '" align="absMiddle" />' : (($r['due_date']-time()) < 604800 ? '<img src="images/icons/coins_error.png" width="16" height="16" border="0" alt="'. SUBSCRIPTION_DUE_WITHIN_7_DAYS . '" title="' . SUBSCRIPTION_DUE_WITHIN_7_DAYS . '" align="absMiddle" />' : '');

					$output .= '<div class="tr" style="cursor:pointer;" onclick="window.location.href=\'?action=view&id=' . $r['vps_id'] . '&ref=' . $pageLink . '\';">
						<div class="td" style="width:3%;" id="status_' . $r['vps_id'] . '">' . $status . '</div>
						<div class="td" style="width:20%;"><acronym title="' . $r['vps_name'] . '">' . $r['vps_name'] . '</acronym></div>
						<div class="td" style="width:20%;"><acronym title="' . $r['location'] . '"><img src="images/flags/' . strtolower($r['country']) . '.png" border="0" align="absMiddle" /> ' . $r['location'] . '</acronym></div>
						<div class="td" style="width:10%;"><acronym title="' . $groupName . '">' . $groupName . '</acronym></div>
						<div class="td" style="width:10%;"><acronym title="' . $providerName . '">' . $providerName . '</acronym></div>
						<div class="td" style="width:3%;">' . $paymentStatus . '</div>
						<div class="td" style="width:10%;">
							<a href="?action=boot&id=' . $r['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_start.png" border="0" alt="' . BOOT . '" title="' . BOOT . '" align="absMiddle" /></a>
							<a href="?action=shutdown&id=' . $r['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_stop.png" border="0" alt="' . SHUTDOWN . '" title="' . SHUTDOWN . '" align="absMiddle" /></a>
							<a href="?action=reboot&id=' . $r['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/control_restart.png" border="0" alt="' . REBOOT . '" title="' . REBOOT . '" align="absMiddle" /></a>
						</div>
						<div class="td" style="width:8%;""><a href="?action=edit&id=' . $r['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/page_white_edit.png" border="0" alt="' . EDIT . '" title="' . EDIT . '" align="absMiddle" /></a> <a href="?action=remove&id=' . $r['vps_id'] . '&ref=' . $pageLink . '"><img src="images/icons/bin.png" border="0" alt="' . REMOVE . '" title="' . REMOVE . '" align="absMiddle" /></a></div>
						<div class="clear"></div>
					</div>';
				}
				$output .= '</div>';
			}
			else{
				$output .= '<p class="red">' . THERE_ARE_NO_RESULTS_FOUND . '</p>';
			}
		}
		else{
			$output .= '<p class="red">' . str_replace('%link%', '?action=add', YOUR_VPS_LIST_IS_EMPTY) . '</p>';
		}
	break;
}

$output .= '<p>&nbsp;</p>
	</div>';

$showSidebar = 1;
include(INCLUDES . 'header.php');
echo $output;
include(INCLUDES . 'footer.php');
?>