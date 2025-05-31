<?php
session_destroy();
showMsg('success', 'You\'ve Been Logged Out!', 'auth/login');
