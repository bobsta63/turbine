<?php

class UtilController extends \BaseController
{

    public function getDeleteTarget($id, $target_hash)
    {
        // We now load in the target's nginx host file and delete the target that's name matches the md5 hash.
        $rule = Rule::find($id);
        if ($rule) {
            $config = new NginxConfig();
            $config->setHostheaders($rule->hostheader);
            $config->readConfig(Setting::getSetting('nginxconfpath') . '/' . $config->serverNameToFileName() . '.enabled.conf');
            $existing_targets = json_decode($config->writeConfig()->toJSON());
            // We now iterate over each of the servers in the config file until we match the 'servers' name with the hash and when we do
            // we delete the host from the configuration file before writing the chagnes to disk...
            foreach ($existing_targets->nlb_servers as $target) {
                if (md5($target->target) == $target_hash) {
                    // Matches the target hash, we will now remove from the config file and break out the foreach.
                    $config->removeServerFromNLB($target->target);
                    break;
                }
            }
            $config->writeConfig()->toFile(Setting::getSetting('nginxconfpath') . '/' . $config->serverNameToFileName() . '.enabled.conf');
            $config->reloadConfig();
        }
        return Redirect::back()
                        ->with('flash_success', 'Target ' . strtolower(Input::get('target')) . ' has been successfully deleted from this rule!');
    }

    public function postAddTarget($id)
    {
        // The post data will enable us to add the new target
        // we'll first validate the data before continueing...
        $validator = Validator::make(
                        array(
                    'target address' => Input::get('target'),
                    'max fails' => Input::get('maxfails'),
                    'fail timeout' => Input::get('failtimeout'),
                    'weight' => Input::get('weight'))
                        , array(
                    'target address' => array('required'),
                    'max fails' => array('integer', 'required'),
                    'fail timeout' => array('alpha_num', 'required'),
                    'weight' => array('numeric', 'between:1, 100'),
                        )
        );

        if ($validator->passes()) {
            $rule = Rule::find($id);
            if ($rule) {
                $config = new NginxConfig();
                $config->setHostheaders($rule->hostheader);
                $config->readConfig(Setting::getSetting('nginxconfpath') . '/' . $config->serverNameToFileName() . '.enabled.conf');
                $config->addServerToNLB(array(
                    strtolower(Input::get('target')), array(
                        'max_fails' => Input::get('maxfails'),
                        'fail_timeout' => Input::get('failtimeout'),
                        'weight' => Input::get('weight'),
                    )
                ));
                $config->writeConfig()->toFile(Setting::getSetting('nginxconfpath') . '/' . $config->serverNameToFileName() . '.enabled.conf');
                $config->reloadConfig();
            }
            return Redirect::back()
                            ->with('flash_success', 'New target ' . strtolower(Input::get('target')) . ' has been added!');
        } else {
            $errors = $validator->messages();
            return Redirect::back()
                            ->withInput()
                            ->with('flash_error', 'The following validation errors occured, please correct them and try again:<br /><br /> * ' . implode('<br /> * ', $errors->all()));
        }
    }

}

?>