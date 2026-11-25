import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:app_links/app_links.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await _requestPermissions();
  runApp(const VJMeetApp());
}

Future<void> _requestPermissions() async {
  await [
    Permission.camera,
    Permission.microphone,
  ].request();
}

class VJMeetApp extends StatelessWidget {
  const VJMeetApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'VJMeet',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primarySwatch: Colors.indigo,
      ),
      home: const WebViewScreen(),
    );
  }
}

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  InAppWebViewController? webViewController;
  late AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;
  
  final InAppWebViewSettings settings = InAppWebViewSettings(
    mediaPlaybackRequiresUserGesture: false,
    allowsInlineMediaPlayback: true,
    javaScriptEnabled: true,
    domStorageEnabled: true,
    // Honor the page's <meta viewport> so phones get the responsive layout
    // instead of a scaled-down "desktop" render.
    useWideViewPort: true,
    loadWithOverviewMode: true,
    supportZoom: false,
    builtInZoomControls: false,
    displayZoomControls: false,
    useHybridComposition: true,
    userAgent: 'VJMeetApp-Android/1.0 (Mobile)',
  );

  @override
  void initState() {
    super.initState();
    _initAppLinks();
  }

  void _initAppLinks() {
    _appLinks = AppLinks();
    _linkSubscription = _appLinks.uriLinkStream.listen((uri) {
      if (webViewController != null) {
        webViewController!.loadUrl(urlRequest: URLRequest(url: WebUri(uri.toString())));
      }
    });
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: WillPopScope(
          onWillPop: () async {
            if (webViewController != null) {
              if (await webViewController!.canGoBack()) {
                await webViewController!.goBack();
                return false;
              }
            }
            return true;
          },
          child: InAppWebView(
            initialUrlRequest: URLRequest(url: WebUri('https://younimatiq.com/?app=true')),
            initialSettings: settings,
            onWebViewCreated: (controller) async {
              webViewController = controller;
              // Check for initial deep link when WebView is ready
              final initialLink = await _appLinks.getInitialLink();
              if (initialLink != null) {
                webViewController!.loadUrl(urlRequest: URLRequest(url: WebUri(initialLink.toString())));
              }
            },
            onPermissionRequest: (controller, request) async {
              return PermissionResponse(
                resources: request.resources,
                action: PermissionResponseAction.GRANT,
              );
            },
            onConsoleMessage: (controller, consoleMessage) {
              debugPrint('WebView Console: ${consoleMessage.message}');
            },
          ),
        ),
      ),
    );
  }
}
