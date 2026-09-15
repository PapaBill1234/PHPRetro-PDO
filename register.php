<?php
require_once('./includes/core.php');
$lang->addLocale("landing.register");

$page['name'] = $lang->loc['pagename.register'];
$refer = false;
$failure = false;
$error = [];
$name = $password = $retypedpassword = $email = $retypedemail = $figure = $gender = '';
$day = $month = $year = 0;

if (isset($_POST['bean_avatarName'])) {
    Csrf::requireValid();
    $name = trim((string) $_POST['bean_avatarName']);
    $password = (string) ($_POST['password'] ?? '');
    $retypedpassword = (string) ($_POST['retypedPassword'] ?? '');
    $email = trim((string) ($_POST['bean_email'] ?? ''));
    $retypedemail = trim((string) ($_POST['bean_retypedEmail'] ?? ''));
    $day = (int) ($_POST['bean_day'] ?? 0);
    $month = (int) ($_POST['bean_month'] ?? 0);
    $year = (int) ($_POST['bean_year'] ?? 0);
    $figure = (string) ($_POST['bean_figure'] ?? '');
    $gender = (string) ($_POST['bean_gender'] ?? '');
    if ($figure === '' && isset($_POST['randomFigure']) && is_string($_POST['randomFigure']) && preg_match('/^([MF])-(.+)$/', $_POST['randomFigure'], $picked)) {
        $gender = $picked[1];
        $figure = $picked[2];
    }
    $acceptTos = (string) ($_POST['bean_termsOfServiceSelection'] ?? '');
    $lang->addLocale("register.errors");

    if (!preg_match('/^[a-z0-9\-=?!@:.]+$/i', $name) || strlen($name) < 1 || strlen($name) > 24 || strncasecmp($name, 'MOD-', 4) === 0) {
        $error['name'] = $lang->loc['error.3'];
        $failure = true;
    }
    if ($password !== $retypedpassword || strlen($password) < 6) {
        $error['password'] = $lang->loc['error.7'];
        $failure = true;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $email !== $retypedemail) {
        $error['mail'] = $lang->loc['error.9'];
        $failure = true;
    }
    if (!checkdate($month, $day, $year)) {
        $error['dob'] = $lang->loc['error.11'];
        $failure = true;
    }
    if ($acceptTos !== 'true') {
        $error['tos'] = $lang->loc['error.12'];
        $failure = true;
    }

    if (!$failure) {
        $db = new Database();
        if ($db->fetchRow("SELECT id FROM users WHERE username = ? LIMIT 1", [$name])) {
            $error['name'] = $lang->loc['error.2'];
            $failure = true;
        }
    }

    if (!$failure) {
        $db = new Database();
        $createdAt = time();
        $db->execute(
            "INSERT INTO users (username, password, mail, mail_verified, account_created, account_day_of_birth, last_login, last_online, look, gender, credits, ip_register, ip_current)
             VALUES (?, ?, ?, '0', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, password_hash($password, PASSWORD_DEFAULT), $email, $createdAt, mktime(0, 0, 0, $month, $day, $year), $createdAt, $createdAt, $figure, $gender, (int) $settings->find('register_start_credits'), substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]
        );
        $userId = (int) $db->insertId();

        if ($settings->find('email_verify_enabled') === '1') {
            $token = bin2hex(random_bytes(32));
            $now = time();
            $db->execute("DELETE FROM phpretro_email_verification_tokens WHERE user_id = ? AND used_at IS NULL", [$userId]);
            $db->execute(
                "INSERT INTO phpretro_email_verification_tokens (user_id, token_hash, created_at, expires_at) VALUES (?, ?, ?, ?)",
                [$userId, hash('sha256', $token), $now, $now + 86400]
            );
            $lang->addLocale("email.confirmationemail");
            $verificationUrl = PATH . '/email?token=' . rawurlencode($token);
            $html = '<p><a href="' . $input->HoloText($verificationUrl) . '">' . $input->HoloText($verificationUrl) . '</a></p>';
            (new HoloMail())->sendSimpleMessage($email, $lang->loc['email.subject'] . ' ' . SHORTNAME, $html);
        } else {
            $db->execute("UPDATE users SET mail_verified = '1' WHERE id = ?", [$userId]);
        }

        header("Location: " . PATH . "/");
        exit;
    }
}

require_once('./templates/register_header.php');

?>
	        	<div id="column1" class="column">
			     		
				<div class="habblet-container ">		
	<?php if($refer == true){ ?>
						<div id="inviter-info">
            <p><?php echo $lang->loc['your.friend']." ".$input->HoloText($referrow[1])." ".$lang->loc['is.waiting']; ?></p>
            <img alt="<?php echo $input->HoloText($referrow[1]); ?>" title="<?php echo $input->HoloText($referrow[1]); ?>" src="<?php echo $user->avatarURL($referrow[2],"b,4,4,sml,1,0"); ?>" />
        </div>
	<?php } ?>
    <form method="post" action="<?php echo PATH; ?>/register" id="registerform" autocomplete="off"><?php echo Csrf::field(); ?>
	<input type="hidden" name="bean.figure" id="register-figure" value="<?php echo $input->HoloText($figure); ?>" />
	<input type="hidden" name="bean.gender" id="register-gender" value="<?php echo $input->HoloText($gender); ?>" />
	<input type="hidden" name="bean.editorState" id="register-editor-state" value="" />
	<?php if($refer == true){ ?><input type="hidden" name="referral" id="register-referrer" value="<?php echo $input->HoloText($referral); ?>" /><?php } ?>
<?php
if(!isset($error['captcha'])){
?>
        <div id="register-column-left" >
            <div id="register-section-2">
                <div class="rounded rounded-blue">
                    <h2 class="heading"><span class="numbering white">2.</span><?php echo $lang->loc['choose.name']; ?></h2>

                    <fieldset id="register-fieldset-name">
	                    <div class="register-label white"><?php echo $lang->loc['habbo.name']; ?></div>
		                <input type="text" name="bean.avatarName" id="register-name" class="register-text" value="<?php echo $input->HoloText($name); ?>" size="25" />
		                <span id="register-name-check-container" style="display:none">
		                    <a class="new-button search-icon" href="#" id="register-name-check"><b><span></span></b><i></i></a>		                
		                </span>
                    </fieldset>
                    <div id="name-error-box">
				<?php if(isset($error['name'])){ ?>
                        <div class="register-error">
                            <div class="rounded rounded-red">
                                <div id="name-error-content">
                                    <?php echo $error['name']; ?>
                                </div>
                            </div>
                        </div>
				<?php } ?>
                    </div>

                </div>
            </div>

            <div id="register-section-3">
                <div id="registration-overlay"></div>
	            <div class="cbb clearfix gray">
    	            <h2 class="title heading"><span class="numbering white">3.</span><?php echo $lang->loc['your.details']; ?>	</h2>
    		        <div class="box-content">

			<?php if(isset($error['password'])){ ?>
	                    <div class="register-error">
	                    	<div class="rounded rounded-red">
								<div id="password-error-content">
									<div><?php echo $error['password']; ?></div>
								</div>
							</div>
	                    </div>
			<?php } ?>

                        <fieldset id="register-fieldset-password">
	                        <div class="register-label"><label for="register-password"><?php echo $lang->loc['password']; ?></label></div>
	                        <div class="register-label"><input type="password" name="password" id="register-password" class="register-text" size="25" value="" /></div>
	                        <div class="register-label"><label for="register-password2"><?php echo $lang->loc['confirm.password']; ?></label></div>
	                        <div class="register-label"><input type="password" name="retypedPassword" id="register-password2" class="register-text" size="25" value="" /></div>
                        </fieldset>
                        <div id="password-error-box"></div>

				<?php if(isset($error['dob'])){ ?>
	                    <div class="register-error">
	                    	<div class="rounded rounded-red">
                            	<div id="birthday-error-content">
	                         	   <div><?php echo $error['dob']; ?></div>
	                        	</div>
	                        </div>
	                    </div>
				<?php } ?>


                        <fieldset>
	                        <div class="register-label"><label><?php echo $lang->loc['dob']; ?></label></div>
							<?php $months = explode("|", $lang->loc['list.months']); ?>
	                        <div id="register-birthday"><select name="bean.day" id="bean_day" class="dateselector"><option value=""><?php echo $lang->loc['day']; ?></option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option></select> <select name="bean.month" id="bean_month" class="dateselector"><option value=""><?php echo $lang->loc['month']; ?></option><option value="1"><?php echo $months[0]; ?></option><option value="2"><?php echo $months[1]; ?></option><option value="3"><?php echo $months[2]; ?></option><option value="4"><?php echo $months[3]; ?></option><option value="5"><?php echo $months[4]; ?></option><option value="6"><?php echo $months[5]; ?></option><option value="7"><?php echo $months[6]; ?></option><option value="8"><?php echo $months[7]; ?></option><option value="9"><?php echo $months[8]; ?></option><option value="10"><?php echo $months[9]; ?></option><option value="11"><?php echo $months[10]; ?></option><option value="12"><?php echo $months[11]; ?></option></select> <select name="bean.year" id="bean_year" class="dateselector"><option value=""><?php echo $lang->loc['year']; ?></option><option value="2008">2008</option><option value="2007">2007</option><option value="2006">2006</option><option value="2005">2005</option><option value="2004">2004</option><option value="2003">2003</option><option value="2002">2002</option><option value="2001">2001</option><option value="2000">2000</option><option value="1999">1999</option><option value="1998">1998</option><option value="1997">1997</option><option value="1996">1996</option><option value="1995">1995</option><option value="1994">1994</option><option value="1993">1993</option><option value="1992">1992</option><option value="1991">1991</option><option value="1990">1990</option><option value="1989">1989</option><option value="1988">1988</option><option value="1987">1987</option><option value="1986">1986</option><option value="1985">1985</option><option value="1984">1984</option><option value="1983">1983</option><option value="1982">1982</option><option value="1981">1981</option><option value="1980">1980</option><option value="1979">1979</option><option value="1978">1978</option><option value="1977">1977</option><option value="1976">1976</option><option value="1975">1975</option><option value="1974">1974</option><option value="1973">1973</option><option value="1972">1972</option><option value="1971">1971</option><option value="1970">1970</option><option value="1969">1969</option><option value="1968">1968</option><option value="1967">1967</option><option value="1966">1966</option><option value="1965">1965</option><option value="1964">1964</option><option value="1963">1963</option><option value="1962">1962</option><option value="1961">1961</option><option value="1960">1960</option><option value="1959">1959</option><option value="1958">1958</option><option value="1957">1957</option><option value="1956">1956</option><option value="1955">1955</option><option value="1954">1954</option><option value="1953">1953</option><option value="1952">1952</option><option value="1951">1951</option><option value="1950">1950</option><option value="1949">1949</option><option value="1948">1948</option><option value="1947">1947</option><option value="1946">1946</option><option value="1945">1945</option><option value="1944">1944</option><option value="1943">1943</option><option value="1942">1942</option><option value="1941">1941</option><option value="1940">1940</option><option value="1939">1939</option><option value="1938">1938</option><option value="1937">1937</option><option value="1936">1936</option><option value="1935">1935</option><option value="1934">1934</option><option value="1933">1933</option><option value="1932">1932</option><option value="1931">1931</option><option value="1930">1930</option><option value="1929">1929</option><option value="1928">1928</option><option value="1927">1927</option><option value="1926">1926</option><option value="1925">1925</option><option value="1924">1924</option><option value="1923">1923</option><option value="1922">1922</option><option value="1921">1921</option><option value="1920">1920</option><option value="1919">1919</option><option value="1918">1918</option><option value="1917">1917</option><option value="1916">1916</option><option value="1915">1915</option><option value="1914">1914</option><option value="1913">1913</option><option value="1912">1912</option><option value="1911">1911</option><option value="1910">1910</option><option value="1909">1909</option><option value="1908">1908</option><option value="1907">1907</option><option value="1906">1906</option><option value="1905">1905</option><option value="1904">1904</option><option value="1903">1903</option><option value="1902">1902</option><option value="1901">1901</option><option value="1900">1900</option></select> </div>
                        </fieldset>

                        <div id="email-error-box">
				<?php if(isset($error['mail'])){ ?>
	                        <div class="register-error">
	                            <div class="rounded rounded-red">
                                    <div id="email-error-content">
	                                    <div><?php echo $error['mail']; ?></div>
	                                </div>
	                            </div>
	                        </div>
				<?php } ?>
                        </div>


                        <fieldset>
	                        <div class="register-label"><label for="register-email"><?php echo $lang->loc['email']; ?></label></div>
	                        <div class="register-label"><input type="text" name="bean.email" id="register-email" class="register-text" value="<?php echo $input->HoloText($email); ?>" size="25" maxlength="48" /></div>
	                        <div class="register-label"><label for="register-email2"><?php echo $lang->loc['confirm.email']; ?></label></div>
	                        <div class="register-label"><input type="text" name="bean.retypedEmail" id="register-email2" class="register-text" value="" size="25" maxlength="48" /></div>
                        </fieldset>

	                    <div id="register-marketing-box">
		                    <input type="checkbox" name="bean.marketing" id="bean_marketing" value="true" checked="checked" />
		                    <label for="bean_marketing"><?php echo $lang->loc['marketing']; ?></label>
	                    </div>                  


                        <fieldset id="register-fieldset-captcha">
							<noscript>
	                            <div class="register-label"><img src="<?php echo PATH; ?>/captcha.jpg" /></div>
	                            <div class="register-label"><label for="register-captcha"><?php echo $lang->loc['type.in.code']; ?></label></div>
	                            <div id="captcha_response"><input type="text" name="bean.captchaResponse" id="recaptcha_response_field" class="register-text" value="" size="25" /></div>
							</noscript>
						</fieldset>

                        <div id="terms-error-box">
				<?php if(isset($error['tos'])){ ?>
                            <div class="register-error">
                                <div class="rounded rounded-red">
                                    <?php echo $lang->loc['error.14']; ?>
                                </div>
                            </div>
				<?php } ?>
                        </div>
                        <fieldset id="register-fieldset-terms">
                            <div class="rounded rounded-darkgray" id="register-terms">
	                            <div id="register-terms-content">
	                                <p><a href="<?php echo PATH; ?>/papers/disclaimer" target="_blank" id="register-terms-link"><?php echo $lang->loc['terms']; ?></a></p>
                                    <p class="last">
                                        <input type="checkbox" name="bean.termsOfServiceSelection" id="register-terms-check" value="true" />
                                        <label for="register-terms-check"><?php echo $lang->loc['i.agree']; ?></label>
                                    </p>
                                </div>
                            </div>
                        </fieldset>
		            </div>
	            </div>
	            <div id="form-validation-error-box" style="display:none">
                    <div class="register-error">
                        <div class="rounded rounded-red">
                            <?php echo $lang->loc['failure']; ?>
                        </div>
                    </div>
	            </div>
	        </div>


        </div>
<?php }else{ ?>
        <div id="register-column-left" >
            <div id="register-section-2">
                <div class="rounded rounded-blue">
                    <h2 class="heading"><span class="numbering white">2.</span><?php echo $lang->loc['choose.name']; ?></h2>

                    <fieldset id="register-fieldset-name">

	                    <div class="register-label white"><?php echo $lang->loc['habbo.name']; ?></div>
	                    <div class="register-input"><?php echo $input->HoloText($name); ?></div>
                    </fieldset>

                </div>
            </div>

            <div id="register-section-3">
				<div id="registration-overlay"></div>
	            <div class="cbb clearfix gray">
    	            <h2 class="title heading"><span class="numbering white">3.</span><?php echo $lang->loc['your.details']; ?></h2>
    		        <div class="box-content">


                        <fieldset id="register-fieldset-password">
	                        <div class="register-label"><label for="register-password"><?php echo $lang->loc['password']; ?></label></div>
	                        <div class="register-input">*******</div>

                        </fieldset>

                        <fieldset>
	                        <div class="register-label"><label><?php echo $lang->loc['dob']; ?></label></div>
	                        <div class="register-input"><?php echo $input->HoloText($month); ?>/<?php echo $input->HoloText($day); ?>/<?php echo $input->HoloText($year); ?></div>
	                    </fieldset>

                        <div id="email-error-box">
                        </div>

                        <fieldset>
	                        <div class="register-label"><label for="register-email"><?php echo $lang->loc['email']; ?></label></div>
	                        <div class="register-input"><?php echo $input->HoloText($email); ?></div>
	                    </fieldset>

	                    <div id="register-marketing-box">
		                    <input type="checkbox" name="bean.marketing" id="bean_marketing" value="true" checked="checked" />
		                    <label for="bean_marketing"><?php echo $lang->loc['marketing']; ?></label>

	                    </div>


                        <fieldset id="register-fieldset-captcha">

                                <div class="register-label"><img id="captcha" src="<?php echo PATH; ?>/captcha.jpg?t=<?php echo time(); ?>&register=1" alt="" width="200" height="60" /></div>
                                <div class="register-label" id="captcha-reload">
                                    <img src="<?php echo PATH; ?>/web-gallery/v2/images/shared_icons/reload_icon.gif" width="15" height="15"/>
                                    <a href="#"><?php echo $lang->loc['cannot.read.capcha']; ?></a>
                                </div>

	                            <div id="captcha-error-box"><div class="register-error"><div class="rounded rounded-red"><?php echo $lang->loc['error.1']; ?></div></div></div>
	                            <div class="register-label"><label for="register-captcha"><?php echo $lang->loc['type.in.code']; ?></label></div>
	                            <div id="captcha_response"><input type="text" name="bean.captchaResponse" id="recaptcha_response_field" class="register-text error" value="" size="25" /></div>
        <script type="text/javascript">
        document.observe("dom:loaded", function() {
            Event.observe($("captcha-reload"), "click", function(e) {Utils.reloadCaptcha()});
        });
        </script>
                        </fieldset>

                        <div id="terms-error-box">
                        </div>

                        <fieldset id="register-fieldset-terms">
                            <div class="rounded rounded-darkgray" id="register-terms">
	                            <div id="register-terms-content">
	                                <p><a href="<?php echo PATH; ?>/papers/termsAndConditions" target="_blank" id="register-terms-link"><?php echo $lang->loc['terms']; ?></a></p>
                                    <p class="last">
                                        <input type="checkbox" name="bean.termsOfServiceSelection" id="register-terms-check" value="true"  checked="checked"/>
                                        <label for="register-terms-check"><?php echo $lang->loc['i.agree']; ?></label>

                                    </p>
                                </div>
                            </div>
                        </fieldset>
		            </div>
	            </div>
	            <div id="form-validation-error-box" style="display:none">
                    <div class="register-error">
                        <div class="rounded rounded-red">

                            <?php echo $lang->loc['failure']; ?>
                        </div>
                    </div>
	            </div>
	        </div>


        </div>
<?php } ?>
        <div id="register-column-right">

            <div id="register-avatar-editor-title">

                <h2 class="heading"><span class="numbering white">1.</span><?php echo $lang->loc['create.habbo']; ?></h2>
            </div>

            <div id="avatar-error-box">
            </div>
            <div id="register-avatar-editor">
                <p><b><?php echo $lang->loc['no.flash.chooser']; ?></b></p>
                <h3><?php echo $lang->loc['girls']; ?></h3>
				<?php $generator = new HoloFigureCheck(); ?>
                <div class="register-avatars clearfix">
					<?php $figure = $generator->generateFigure(false,"F"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="F-<?php echo $figure[0]; ?>" />
	                </div>
					<?php $figure = $generator->generateFigure(false,"F"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="F-<?php echo $figure[0]; ?>" />
	                </div>
					<?php $figure = $generator->generateFigure(false,"F"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="F-<?php echo $figure[0]; ?>" />
	                </div>
                </div>
                <h3><?php echo $lang->loc['boys']; ?></h3>
                <div class="register-avatars clearfix">
					<?php $figure = $generator->generateFigure(false,"M"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="M-<?php echo $figure[0]; ?>" />
	                </div>
					<?php $figure = $generator->generateFigure(false,"M"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="M-<?php echo $figure[0]; ?>" />
	                </div>
					<?php $figure = $generator->generateFigure(false,"M"); ?>
	                <div class="register-avatar" style="background-image: url(<?php echo $user->avatarURL($figure[0],"b,4,4,sml,1,0"); ?>)">
	                    <input type="radio" name="randomFigure" value="M-<?php echo $figure[0]; ?>" />
	                </div>
	            </div>
                <p><?php echo $lang->loc['dislike']; ?></p>
            </div>

            <div id="register-buttons">
                <input type="submit" value="<?php echo $lang->loc['continue']; ?>" class="continue" id="register-button-continue" />
                <a href="<?php echo PATH; ?>/register/cancel" class="cancel"><?php echo $lang->loc['exit.register']; ?></a>
            </div>
	    </div>
    </form>
	<script type="text/javascript">
	HabboView.add(function() {
		if ($("register-name")) {
			Event.observe($("register-name"), "blur", function() {
				if ($F("register-name") != "" && RegistrationForm.Validator._nameCheckNeeded) {
					RegistrationForm.Validator._checkName();
				}
			});
		}
		var radios = $$("input[name='randomFigure']");
		var applyFigure = function(radio) {
			if (!radio || !radio.value) { return; }
			var value = String(radio.value);
			var dash = value.indexOf("-");
			if (dash < 1) { return; }
			if ($("register-gender")) { $("register-gender").value = value.substring(0, dash); }
			if ($("register-figure")) { $("register-figure").value = value.substring(dash + 1); }
		};
		radios.each(function(radio) {
			Event.observe(radio, "click", function() { applyFigure(radio); });
			Event.observe(radio, "change", function() { applyFigure(radio); });
		});
		if (radios.length > 0 && !$F("register-figure")) {
			radios[0].checked = true;
			applyFigure(radios[0]);
		}
		var origComplete = RegistrationForm.Validator._onCheckNameAvailabilityComplete;
		RegistrationForm.Validator._onCheckNameAvailabilityComplete = function(C, D) {
			if (!D || typeof D !== "object") {
				$("register-name").removeClassName("register-loading");
				RegistrationForm.Validator._ajaxCheckInProgress = false;
				RegistrationForm.Validator._showErrorState($("register-name"), false);
				return;
			}
			origComplete(C, D);
		};
	});
	</script>
	
						
							
					
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

			 

</div>
<?php

require('./templates/login_footer.php');

?>
