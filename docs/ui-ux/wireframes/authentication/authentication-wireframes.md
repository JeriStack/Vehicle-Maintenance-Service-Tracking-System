# Wireframe: Authentication Screens (Login, Register, Password Recovery)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-WF-AUTH-001  
**Target Screens:** `SCR-AUTH-01` (Login), `SCR-AUTH-02` (Register), `SCR-AUTH-03` (Forgot Password), `SCR-AUTH-04` (Reset Password)  
**Target Roles:** `GUEST` (Public Access)  
**Layout Shell:** Unauthenticated Centered Glassmorphism Card  

---

## 1. Login Screen Wireframe (`SCR-AUTH-01`)

### Desktop Layout Variant (Viewport: >= 992px)
```text
+----------------------------------------------------------------------------------------------------+
|  [Background: Rich Dark Automotive Wallpaper with 65% Navy Tint Overlay (rgba(10, 15, 30, 0.65))]  |
|                                                                                                    |
|                               +-------------------------------------+                              |
|                               |          [ VMS Tracker Logo ]       |                              |
|                               |              VMS Tracker            |                              |
|                               |       Sign in to manage vehicles    |                              |
|                               |-------------------------------------|                              |
|                               |                                     |                              |
|                               |  Email Address                      |                              |
|                               |  [ @ | user@example.com           ] |                              |
|                               |                                     |                              |
|                               |  Password                           |                              |
|                               |  [ * | ••••••••••••••••         |👁️] |                              |
|                               |                                     |                              |
|                               |  [x] Remember me    Forgot password?|                              |
|                               |                                     |                              |
|                               |  +-------------------------------+  |                              |
|                               |  |        [ Sign In ]            |  |  <-- Primary Accent Button   |
|                               |  +-------------------------------+  |                              |
|                               |                                     |                              |
|                               |    Don't have an account?           |                              |
|                               |         Register here               |                              |
|                               +-------------------------------------+                              |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

### Mobile Layout Variant (Viewport: < 576px)
```text
+------------------------------------------+
|          [ VMS Tracker Logo ]            |
|              VMS Tracker                 |
|       Sign in to manage vehicles         |
|------------------------------------------|
|                                          |
|  Email Address                           |
|  [ @ | user@example.com                ] |
|                                          |
|  Password                                |
|  [ * | ••••••••••••••••              |👁️] |
|                                          |
|  [x] Remember me                         |
|  Forgot password?                        |
|                                          |
|  +------------------------------------+  |
|  |             Sign In                |  |  <-- 48px Touch Height
|  +------------------------------------+  |
|                                          |
|  Don't have an account?                  |
|  [ Register here ]                       |
|                                          |
+------------------------------------------+
```

### Operational States & Error Handling
```text
[ Authentication Error State ]:
+----------------------------------------------------------------------+
|  [!] Invalid email address or password. Please verify credentials.   |  <-- Generic Message
+----------------------------------------------------------------------+

[ Locked / Rate-Limited State ]:
+----------------------------------------------------------------------+
|  [!] Too many failed attempts. Account locked for 15 minutes.        |
+----------------------------------------------------------------------+
```

---

## 2. Registration Screen Wireframe (`SCR-AUTH-02`)

### Desktop Layout Variant (Viewport: >= 992px)
```text
+----------------------------------------------------------------------------------------------------+
|                               +---------------------------------------------+                      |
|                               |             [ VMS Tracker Logo ]            |                      |
|                               |           Create Your Garage Account        |                      |
|                               |       Join VMS to track vehicle maintenance |                      |
|                               |---------------------------------------------|                      |
|                               |                                             |                      |
|                               |  Full Name                                  |                      |
|                               |  [ John Doe                               ] |                      |
|                               |                                             |                      |
|                               |  Email Address                              |                      |
|                               |  [ john.doe@example.com                   ] |                      |
|                               |                                             |                      |
|                               |  Phone Number (Optional)                    |                      |
|                               |  [ +1 (555) 000-0000                      ] |                      |
|                               |                                             |                      |
|                               |  Password                                   |                      |
|                               |  [ •••••••••••••••••••••••••••••••••••• |👁️] |                      |
|                               |  [Strength: ===== Strong (8+ chars, mixed)] |                      |
|                               |                                             |                      |
|                               |  Confirm Password                           |                      |
|                               |  [ •••••••••••••••••••••••••••••••••••• |👁️] |                      |
|                               |                                             |                      |
|                               |  [x] I agree to Terms & Privacy Policy      |                      |
|                               |                                             |                      |
|                               |  +---------------------------------------+  |                      |
|                               |  |       [ Create Owner Account ]        |  |                      |
|                               |  +---------------------------------------+  |                      |
|                               |                                             |                      |
|                               |      Already registered? Sign In            |                      |
|                               +---------------------------------------------+                      |
+----------------------------------------------------------------------------------------------------+
```

---

## 3. Password Recovery Screens (`SCR-AUTH-03`, `SCR-AUTH-04`)

### Forgot Password (`SCR-AUTH-03`)
```text
+---------------------------------------------------------------------+
|                      Reset Your Password                            |
|  Enter your account email to receive a password recovery link.      |
|---------------------------------------------------------------------|
|  Email Address                                                      |
|  [ user@example.com                                               ] |
|                                                                     |
|  +---------------------------------------------------------------+  |
|  |                   Send Recovery Link                          |  |
|  +---------------------------------------------------------------+  |
|                                                                     |
|  Back to Login                                                      |
+---------------------------------------------------------------------+
```

### Reset Password with Token (`SCR-AUTH-04`)
```text
+---------------------------------------------------------------------+
|                       Set New Password                              |
|  Your token has been verified. Enter your new password below.       |
|---------------------------------------------------------------------|
|  New Password                                                       |
|  [ ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••• |👁️] |
|                                                                     |
|  Confirm New Password                                               |
|  [ ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••• |👁️] |
|                                                                     |
|  +---------------------------------------------------------------+  |
|  |                   Update Password                             |  |
|  +---------------------------------------------------------------+  |
+---------------------------------------------------------------------+
```

