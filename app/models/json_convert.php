<?php

	$json = 
'{
  "is_success": true,
  "clincalURLDomains": [
    "medscape.com",
    "webmd.com",
    "nih.gov",
    "merckmanuals.com",
    "mayoclinic.org",
    "clevelandclinic.org",
    "nejm.org",
    "icsi.org"
  ],
  "clinicalURLRegex": [
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?medscape\\\.com$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?webmd\\\.com$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?nih\\\.gov$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?merckmanuals\\\.com$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?mayoclinic\\\.org$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?clevelandclinic\\\.org$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?nejm\\\.org$/",
    "/^(?:http(?:s)?:\\\/\\\/)?(?:[^\\\.]+\\\.)?icsi\\\.org$/"
  ]
}';

	$data = json_decode($json);
	var_export($data);
