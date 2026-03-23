package com.example.uno_projet

import android.Manifest
import android.app.Application
import android.bluetooth.BluetoothManager
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Bundle
import android.provider.Settings
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import com.example.uno_projet.bluetooth.BluetoothDeviceInfo
import com.example.uno_projet.data.SensorReading
import com.example.uno_projet.ui.theme.UNO_ProjetTheme
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import kotlin.math.cos
import kotlin.math.min
import kotlin.math.sin

class MainActivity : ComponentActivity() {
    private val viewModel by viewModels<MainViewModel> {
        MainViewModel.factory(application as Application)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            UNO_ProjetTheme {
                MonitorApp(viewModel = viewModel)
            }
        }
    }
}

@Composable
private fun MonitorApp(viewModel: MainViewModel) {
    val context = LocalContext.current
    val uiState by viewModel.uiState.collectAsState()
    var selectedHistoryReading by remember { mutableStateOf<SensorReading?>(null) }
    val bluetoothManager = remember {
        context.getSystemService(BluetoothManager::class.java)
    }
    val adapterEnabled = bluetoothManager?.adapter?.isEnabled == true
    val permissions = remember {
        arrayOf(
            Manifest.permission.BLUETOOTH_CONNECT,
            Manifest.permission.BLUETOOTH_SCAN
        )
    }
    val hasPermissions = permissions.all { permission ->
        ContextCompat.checkSelfPermission(context, permission) == PackageManager.PERMISSION_GRANTED
    }
    val statusText = connectionStatusText(uiState = uiState)

    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) {
        viewModel.refreshBondedDevices()
    }

    LaunchedEffect(hasPermissions) {
        if (hasPermissions) {
            viewModel.refreshBondedDevices()
        }
    }

    Scaffold(modifier = Modifier.fillMaxSize()) { padding ->
        if (!uiState.isBluetoothAvailable) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentAlignment = Alignment.Center
            ) {
                Text(stringResource(R.string.bluetooth_not_supported))
            }
            return@Scaffold
        }

        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(
                    Brush.verticalGradient(
                        colors = listOf(Color(0xFFF4F8FB), Color(0xFFEAF1F7))
                    )
                ),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            item {
                HeaderCard(
                    hasPermissions = hasPermissions,
                    bluetoothEnabled = adapterEnabled,
                    statusText = statusText,
                    lastRecordedAt = uiState.latestReading?.recordedAt,
                    onRequestPermissions = { permissionLauncher.launch(permissions) },
                    onOpenBluetoothSettings = {
                        context.startActivity(Intent(Settings.ACTION_BLUETOOTH_SETTINGS))
                    }
                )
            }

            item {
                DeviceSelectorCard(
                    devices = uiState.bondedDevices,
                    selectedAddress = uiState.selectedDeviceAddress,
                    isConnecting = uiState.isConnecting,
                    onSelect = viewModel::selectDevice,
                    onRefresh = viewModel::refreshBondedDevices,
                    onConnect = viewModel::connectToSelectedDevice,
                    onDisconnect = viewModel::disconnect,
                    enabled = hasPermissions && adapterEnabled
                )
            }

            item {
                LiveMetricsCard(
                    latestReading = uiState.latestReading,
                    rawMessage = uiState.lastMessage
                )
            }

            item {
                RadarCard(
                    latestReading = uiState.latestReading,
                    radarTrail = uiState.radarTrail
                )
            }

            item {
                HistoryCard(
                    readings = uiState.recentReadings,
                    onSelectReading = { selectedHistoryReading = it }
                )
            }
        }

        selectedHistoryReading?.let { reading ->
            HistoryDetailDialog(
                reading = reading,
                onDismiss = { selectedHistoryReading = null }
            )
        }
    }
}

@Composable
private fun connectionStatusText(uiState: MonitorUiState): String {
    return when (uiState.connectionStatus) {
        ConnectionStatus.DISCONNECTED -> stringResource(R.string.status_disconnected)
        ConnectionStatus.SELECT_DEVICE -> stringResource(R.string.status_select_device)
        ConnectionStatus.CONNECTING -> stringResource(
            R.string.status_connecting,
            uiState.connectionDetail.orEmpty()
        )

        ConnectionStatus.STREAMING -> stringResource(R.string.status_streaming)
        ConnectionStatus.UNRECOGNIZED -> stringResource(R.string.status_unrecognized)
        ConnectionStatus.FAILED -> stringResource(
            R.string.status_failed,
            uiState.connectionDetail ?: stringResource(R.string.default_error)
        )
    }
}

@Composable
private fun HeaderCard(
    hasPermissions: Boolean,
    bluetoothEnabled: Boolean,
    statusText: String,
    lastRecordedAt: Long?,
    onRequestPermissions: () -> Unit,
    onOpenBluetoothSettings: () -> Unit
) {
    val formatter = rememberFrenchDateFormatter("dd MMM yyyy HH:mm:ss")

    Card(
        colors = CardDefaults.cardColors(containerColor = Color(0xFF0C1D2D))
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            Text(
                text = stringResource(R.string.screen_title),
                color = Color.White,
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = stringResource(R.string.screen_subtitle),
                color = Color(0xFFC6E0F8),
                style = MaterialTheme.typography.bodyLarge
            )
            StatusCapsule(text = statusText)
            Text(
                text = buildString {
                    append(stringResource(R.string.label_last_update))
                    append(": ")
                    append(
                        lastRecordedAt?.let { formatter.format(Date(it)) }
                            ?: stringResource(R.string.text_not_available)
                    )
                },
                color = Color(0xFF89B7D8),
                style = MaterialTheme.typography.bodyMedium
            )
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedButton(
                    onClick = onRequestPermissions,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(
                        if (hasPermissions) {
                            stringResource(R.string.action_permissions_ok)
                        } else {
                            stringResource(R.string.action_grant_permissions)
                        }
                    )
                }
                OutlinedButton(
                    onClick = onOpenBluetoothSettings,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(
                        if (bluetoothEnabled) {
                            stringResource(R.string.action_bluetooth_enabled)
                        } else {
                            stringResource(R.string.action_open_bluetooth_settings)
                        }
                    )
                }
            }
        }
    }
}

@Composable
private fun StatusCapsule(text: String) {
    Box(
        modifier = Modifier
            .background(Color(0x1F8FE8FF), RoundedCornerShape(999.dp))
            .border(1.dp, Color(0x337ED7F7), RoundedCornerShape(999.dp))
            .padding(horizontal = 12.dp, vertical = 8.dp)
    ) {
        Text(
            text = text,
            color = Color(0xFFD8F4FF),
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DeviceSelectorCard(
    devices: List<BluetoothDeviceInfo>,
    selectedAddress: String?,
    isConnecting: Boolean,
    onSelect: (String) -> Unit,
    onRefresh: () -> Unit,
    onConnect: () -> Unit,
    onDisconnect: () -> Unit,
    enabled: Boolean
) {
    var expanded by remember { mutableStateOf(false) }
    val selectedDevice = devices.firstOrNull { it.address == selectedAddress }

    Card {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = stringResource(R.string.section_bluetooth_module),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            ExposedDropdownMenuBox(
                expanded = expanded,
                onExpandedChange = { expanded = !expanded }
            ) {
                OutlinedTextField(
                    value = selectedDevice?.let { "${it.name} (${it.address})" }
                        ?: stringResource(R.string.text_no_device_selected),
                    onValueChange = {},
                    readOnly = true,
                    label = { Text(stringResource(R.string.label_paired_devices)) },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                    modifier = Modifier
                        .menuAnchor(MenuAnchorType.PrimaryNotEditable)
                        .fillMaxWidth()
                )
                ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                    devices.forEach { device ->
                        DropdownMenuItem(
                            text = { Text("${device.name} (${device.address})") },
                            onClick = {
                                onSelect(device.address)
                                expanded = false
                            }
                        )
                    }
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedButton(
                    onClick = onRefresh,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(stringResource(R.string.action_refresh_devices))
                }
                Button(
                    onClick = onConnect,
                    enabled = enabled && selectedAddress != null && !isConnecting,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(
                        if (isConnecting) {
                            stringResource(R.string.status_connecting_short)
                        } else {
                            stringResource(R.string.action_start_stream)
                        }
                    )
                }
            }
            OutlinedButton(
                onClick = onDisconnect,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(stringResource(R.string.action_disconnect))
            }
            if (devices.isEmpty()) {
                Text(stringResource(R.string.text_no_paired_devices))
            }
        }
    }
}

@Composable
private fun LiveMetricsCard(
    latestReading: SensorReading?,
    rawMessage: String?
) {
    val formatter = rememberFrenchDateFormatter("dd MMM yyyy HH:mm:ss")

    Card {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = stringResource(R.string.section_live_data),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                MetricBox(
                    title = stringResource(R.string.label_angle),
                    value = latestReading?.let {
                        stringResource(R.string.value_angle, it.angleDeg)
                    } ?: stringResource(R.string.text_no_measure),
                    modifier = Modifier.weight(1f)
                )
                MetricBox(
                    title = stringResource(R.string.label_distance),
                    value = latestReading?.let {
                        stringResource(R.string.value_distance, it.distanceCm)
                    } ?: stringResource(R.string.text_no_measure),
                    modifier = Modifier.weight(1f)
                )
            }
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                MetricBox(
                    title = stringResource(R.string.label_temperature),
                    value = latestReading?.let {
                        stringResource(R.string.value_temperature, it.temperature)
                    } ?: stringResource(R.string.text_no_measure),
                    modifier = Modifier.weight(1f)
                )
                MetricBox(
                    title = stringResource(R.string.label_alarm),
                    value = latestReading?.let {
                        if (it.alarmEnabled) {
                            stringResource(R.string.value_alarm_on)
                        } else {
                            stringResource(R.string.value_alarm_off)
                        }
                    } ?: stringResource(R.string.text_no_measure),
                    modifier = Modifier.weight(1f)
                )
            }
            Text(
                text = buildString {
                    append(stringResource(R.string.label_last_update))
                    append(": ")
                    append(
                        latestReading?.let { formatter.format(Date(it.recordedAt)) }
                            ?: stringResource(R.string.text_not_available)
                    )
                },
                style = MaterialTheme.typography.bodyMedium
            )
            Text(
                text = buildString {
                    append(stringResource(R.string.label_last_frame))
                    append(": ")
                    append(rawMessage ?: stringResource(R.string.text_waiting_bluetooth_data))
                },
                style = MaterialTheme.typography.bodyMedium
            )
        }
    }
}

@Composable
private fun MetricBox(title: String, value: String, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier
            .border(1.dp, Color(0xFFD6DEE6), RoundedCornerShape(18.dp))
            .background(Color(0xFFFDFEFF), RoundedCornerShape(18.dp))
            .padding(16.dp)
    ) {
        Text(title, color = Color(0xFF557086))
        Spacer(modifier = Modifier.height(6.dp))
        Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun RadarCard(
    latestReading: SensorReading?,
    radarTrail: List<SensorReading>
) {
    Card {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = stringResource(R.string.section_radar),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            RadarView(
                angleDeg = latestReading?.angleDeg,
                distanceCm = latestReading?.distanceCm,
                alarmEnabled = latestReading?.alarmEnabled == true,
                radarTrail = radarTrail,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(320.dp)
            )
            Text(stringResource(R.string.text_radar_hint))
        }
    }
}

private const val RADAR_START_ANGLE = 160f
private const val RADAR_END_ANGLE = 20f
private const val RADAR_SECTOR_SWEEP = RADAR_START_ANGLE - RADAR_END_ANGLE
private const val RADAR_MAX_DISTANCE_CM = 300.0
private const val RADAR_DEFAULT_ANGLE = 90f
private const val RADAR_HALF_CIRCLE_SWEEP = 180f

@Composable
private fun RadarView(
    angleDeg: Double?,
    distanceCm: Double?,
    alarmEnabled: Boolean,
    radarTrail: List<SensorReading>,
    modifier: Modifier = Modifier
) {
    val targetAngle = normalizeRadarAngle(angleDeg?.toFloat() ?: RADAR_DEFAULT_ANGLE)
    val displayedAngle by animateFloatAsState(
        targetValue = targetAngle,
        animationSpec = tween(durationMillis = 220),
        label = "radar_angle"
    )

    Canvas(
        modifier = modifier
            .background(Color(0xFF081420), RoundedCornerShape(24.dp))
            .padding(12.dp)
    ) {
        val center = Offset(size.width / 2f, size.height * 0.92f)
        val radius = min(size.width * 0.46f, size.height * 0.82f)
        val gridColor = Color(0xFF1CB36C)
        val glowColor = Color(0x6627F58A)
        val sectorShade = Color(0x1227F58A)
        val sectorTopLeft = Offset(center.x - radius, center.y - radius)
        val sectorSize = Size(radius * 2f, radius * 2f)
        val fullTopLeft = Offset(center.x - radius, center.y - radius)
        val fullSize = Size(radius * 2f, radius * 2f)

        drawArc(
            color = sectorShade,
            startAngle = 360f - RADAR_START_ANGLE,
            sweepAngle = RADAR_SECTOR_SWEEP,
            useCenter = true,
            topLeft = sectorTopLeft,
            size = sectorSize
        )

        repeat(4) { index ->
            drawArc(
                color = if (index == 3) gridColor else glowColor,
                startAngle = 180f,
                sweepAngle = RADAR_HALF_CIRCLE_SWEEP,
                useCenter = false,
                topLeft = Offset(
                    center.x - (radius * (index + 1) / 4f),
                    center.y - (radius * (index + 1) / 4f)
                ),
                size = Size(
                    radius * (index + 1) / 2f,
                    radius * (index + 1) / 2f
                ),
                style = Stroke(width = if (index == 3) 4f else 2f)
            )
        }

        drawArc(
            color = gridColor,
            startAngle = 180f,
            sweepAngle = RADAR_HALF_CIRCLE_SWEEP,
            useCenter = false,
            topLeft = fullTopLeft,
            size = fullSize,
            style = Stroke(width = 4f)
        )

        val semicircleLeft = pointForRadarAngle(center, radius, 180f)
        val semicircleRight = pointForRadarAngle(center, radius, 0f)
        val leftBoundary = pointForRadarAngle(center, radius, RADAR_START_ANGLE)
        val rightBoundary = pointForRadarAngle(center, radius, RADAR_END_ANGLE)

        drawLine(
            color = gridColor,
            start = semicircleLeft,
            end = semicircleRight,
            strokeWidth = 2f
        )
        drawLine(
            color = gridColor,
            start = center,
            end = leftBoundary,
            strokeWidth = 2f
        )
        drawLine(
            color = gridColor,
            start = center,
            end = rightBoundary,
            strokeWidth = 2f
        )
        drawLine(
            color = glowColor,
            start = center,
            end = pointForRadarAngle(center, radius, 90f),
            strokeWidth = 2f
        )

        drawArc(
            brush = Brush.sweepGradient(
                listOf(Color.Transparent, Color(0xAA3BFF9A), Color.Transparent),
                center = center
            ),
            startAngle = -(displayedAngle + 10f),
            sweepAngle = 20f,
            useCenter = true,
            topLeft = sectorTopLeft,
            size = sectorSize,
            alpha = 0.55f
        )

        val scanTarget = pointForRadarAngle(center, radius, displayedAngle)
        drawLine(
            color = Color(0xFF70F7B2),
            start = center,
            end = scanTarget,
            strokeWidth = 3f,
            cap = StrokeCap.Round
        )

        radarTrail
            .asReversed()
            .forEachIndexed { index, reading ->
                val normalizedDistance =
                    (reading.distanceCm.coerceIn(0.0, RADAR_MAX_DISTANCE_CM) / RADAR_MAX_DISTANCE_CM).toFloat()
                val trailPoint = pointForRadarAngle(
                    center = center,
                    radius = radius * normalizedDistance,
                    angleDegrees = normalizeRadarAngle(reading.angleDeg.toFloat())
                )
                val alpha =
                    ((index + 1).toFloat() / radarTrail.size.coerceAtLeast(1)).coerceIn(0.18f, 0.72f)
                drawCircle(
                    color = if (reading.alarmEnabled) {
                        Color(0xFFFF6B6B).copy(alpha = alpha)
                    } else {
                        Color(0xFF8DFFB7).copy(alpha = alpha)
                    },
                    radius = 6f + (alpha * 4f),
                    center = trailPoint
                )
            }

        if (distanceCm != null) {
            val normalized = (distanceCm.coerceIn(0.0, RADAR_MAX_DISTANCE_CM) / RADAR_MAX_DISTANCE_CM).toFloat()
            val targetRadius = radius * normalized
            val target = pointForRadarAngle(center, targetRadius, displayedAngle)

            drawLine(
                color = Color(0xFFFFED8B),
                start = center,
                end = target,
                strokeWidth = 4f,
                cap = StrokeCap.Round
            )
            drawCircle(
                color = if (alarmEnabled) Color(0xFFFF3B30) else Color(0xFFFF7043),
                radius = 14f,
                center = target
            )
            drawCircle(
                color = if (alarmEnabled) Color(0x66FF3B30) else Color(0x55FF7043),
                radius = 28f,
                center = target
            )
        }
    }
}

private fun normalizeRadarAngle(angleDegrees: Float): Float {
    return angleDegrees.coerceIn(RADAR_END_ANGLE, RADAR_START_ANGLE)
}

private fun pointForRadarAngle(center: Offset, radius: Float, angleDegrees: Float): Offset {
    val angleRadians = Math.toRadians(angleDegrees.toDouble())
    return Offset(
        x = center.x + (cos(angleRadians) * radius).toFloat(),
        y = center.y - (sin(angleRadians) * radius).toFloat()
    )
}

@Composable
private fun HistoryCard(
    readings: List<SensorReading>,
    onSelectReading: (SensorReading) -> Unit
) {
    val formatter = rememberFrenchDateFormatter("dd MMM HH:mm:ss")

    Card {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Text(
                text = stringResource(R.string.section_history),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = stringResource(R.string.text_history_count, readings.size),
                color = Color(0xFF607D8B),
                style = MaterialTheme.typography.bodyMedium
            )
            Text(
                text = stringResource(R.string.text_history_hint),
                color = Color(0xFF607D8B),
                style = MaterialTheme.typography.bodySmall
            )
            if (readings.isEmpty()) {
                Text(stringResource(R.string.text_no_history))
            } else {
                readings.forEachIndexed { index, reading ->
                    HistoryRow(
                        reading = reading,
                        formatter = formatter,
                        onClick = { onSelectReading(reading) }
                    )
                    if (index != readings.lastIndex) {
                        HorizontalDivider()
                    }
                }
            }
        }
    }
}

@Composable
private fun HistoryRow(
    reading: SensorReading,
    formatter: SimpleDateFormat,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 6.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = formatter.format(Date(reading.recordedAt)),
                fontWeight = FontWeight.SemiBold
            )
            Text(
                text = reading.rawMessage,
                color = Color(0xFF607D8B),
                style = MaterialTheme.typography.bodySmall
            )
        }
        Spacer(modifier = Modifier.size(12.dp))
        Column(horizontalAlignment = Alignment.End) {
            Text(stringResource(R.string.value_angle, reading.angleDeg))
            Text(stringResource(R.string.value_temperature, reading.temperature))
            Text(stringResource(R.string.value_distance, reading.distanceCm))
            Text(
                if (reading.alarmEnabled) {
                    stringResource(R.string.value_alarm_on)
                } else {
                    stringResource(R.string.value_alarm_off)
                }
            )
        }
    }
}

@Composable
private fun HistoryDetailDialog(
    reading: SensorReading,
    onDismiss: () -> Unit
) {
    val formatter = rememberFrenchDateFormatter("dd MMM yyyy HH:mm:ss")

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(
                text = stringResource(R.string.dialog_history_title),
                fontWeight = FontWeight.Bold
            )
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(
                    text = buildString {
                        append(stringResource(R.string.label_last_update))
                        append(": ")
                        append(formatter.format(Date(reading.recordedAt)))
                    }
                )
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    MetricBox(
                        title = stringResource(R.string.label_angle),
                        value = stringResource(R.string.value_angle, reading.angleDeg),
                        modifier = Modifier.weight(1f)
                    )
                    MetricBox(
                        title = stringResource(R.string.label_distance),
                        value = stringResource(R.string.value_distance, reading.distanceCm),
                        modifier = Modifier.weight(1f)
                    )
                }
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    MetricBox(
                        title = stringResource(R.string.label_temperature),
                        value = stringResource(R.string.value_temperature, reading.temperature),
                        modifier = Modifier.weight(1f)
                    )
                    MetricBox(
                        title = stringResource(R.string.label_alarm),
                        value = if (reading.alarmEnabled) {
                            stringResource(R.string.value_alarm_on)
                        } else {
                            stringResource(R.string.value_alarm_off)
                        },
                        modifier = Modifier.weight(1f)
                    )
                }
                RadarView(
                    angleDeg = reading.angleDeg,
                    distanceCm = reading.distanceCm,
                    alarmEnabled = reading.alarmEnabled,
                    radarTrail = emptyList(),
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(220.dp)
                )
                Text(
                    text = buildString {
                        append(stringResource(R.string.label_last_frame))
                        append(": ")
                        append(reading.rawMessage)
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = Color(0xFF607D8B)
                )
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text(stringResource(R.string.action_close))
            }
        }
    )
}

@Composable
private fun rememberFrenchDateFormatter(pattern: String): SimpleDateFormat {
    return remember(pattern) {
        SimpleDateFormat(pattern, Locale.FRANCE)
    }
}
