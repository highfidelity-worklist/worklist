<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
?>
                <input name="phone_edit" type="hidden" id="phone_edit" value="0"/>
                <p><label>Cell Phone Number<br />
                <input type="text" name="phone" id="phone" size="35" value="<?php echo $phone ?>" />
                </label><br/>
                <em id="phone_helper">
<?php if (!empty($smsaddr))
{
?>
Your bid updates will be sent to <?php echo $smsaddr ?></em>
<?php
} else {
?>
Receive bid updates as text messages on your phone.</em>
<?php
}
?>
                </p>

                <div id="sms" <?php echo (empty($phone)?'style="display:none"':'') ?>>

                    <div id="sms-country">
                        <p><label>Country<br />
                        <select id="country" name="country" style="width:274px">
                            <?php if (empty($country)) { ?>
                            <option value="" selected></option>
                            <?php } ?>
                            <?php foreach ($countrylist as $code=>$cname) {
                                  if (isset($smslist[$code])) { ?>
                            <option value="<?php echo $code ?>" <?php echo ($code == $country ? "selected" : "") ?>><?php echo $cname ?></option>
                            <?php } 
                                  } ?>
                            <option value="--" <?php echo ($country == '--' ? "selected" : "") ?>>(Other)</option>
                        </select>
                        </label><br/>
                        </p>
                    </div>

                    <div id="sms-provider" <?php echo ((empty($country) || $country == '--')?'style="display:none"':'') ?>>
                        <p><label>Wireless Provider<br />
			<input name="stored-provider" type="hidden" id="stored-provider" value="<?php echo $provider; ?>" />
                        <select id="provider" name="provider" style="width:274px">
                            <option value="--" <?php echo ((!empty($provider) && $provider{0} == '+') ? "selected" : "") ?>>(Other)</option>
                        </select>
                        </label><br/>
                        </p>
                    </div>

		    <div id="sms-other" <?php echo ((empty($provider) || $provider{0}!='+')?'style="display:none"':'') ?>>
                        <p><label>SMS Address<br />
			<input type="text" id="smsaddr" name="smsaddr" size="35" value="<?php echo (!empty($smsaddr)?$smsaddr:((!empty($provider) && $provider{0} == '+')?substr($provider, 1):'')) ?>" />
                        </label><br/>
                        <em id="sms_helper">Please enter the email address for sending text messages.</em>
                        </p>
                    </div>

                </div>
