<?php
/**
 * Copyright 2015 Venditan Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// This might be used in a cron that runs once every hour, but the job take a couple of minutes to run
// If we want to record some fact along with each heartbeat
\Venditan\Success::expect('Regular job')->every('65m')->sms('07000000000')->message('Took 30 seconds');