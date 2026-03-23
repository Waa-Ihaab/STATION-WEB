package com.example.uno_projet

import android.app.Application
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothManager
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.uno_projet.bluetooth.BluetoothDeviceInfo
import com.example.uno_projet.bluetooth.BluetoothSerialManager
import com.example.uno_projet.data.SensorReading
import com.example.uno_projet.data.SensorRepository
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.util.Locale
import java.util.regex.Pattern

enum class ConnectionStatus {
    DISCONNECTED,
    SELECT_DEVICE,
    CONNECTING,
    STREAMING,
    UNRECOGNIZED,
    FAILED
}

data class MonitorUiState(
    val bondedDevices: List<BluetoothDeviceInfo> = emptyList(),
    val selectedDeviceAddress: String? = null,
    val connectionStatus: ConnectionStatus = ConnectionStatus.DISCONNECTED,
    val connectionDetail: String? = null,
    val latestReading: SensorReading? = null,
    val recentReadings: List<SensorReading> = emptyList(),
    val radarTrail: List<SensorReading> = emptyList(),
    val lastMessage: String? = null,
    val isBluetoothAvailable: Boolean = true,
    val isConnecting: Boolean = false
)

class MainViewModel(application: Application) : AndroidViewModel(application) {
    private val repository = SensorRepository(application)
    private val bluetoothAdapter: BluetoothAdapter? =
        application.getSystemService(BluetoothManager::class.java)?.adapter
    private val bluetoothManager = BluetoothSerialManager(bluetoothAdapter)

    private val _uiState = MutableStateFlow(
        MonitorUiState(
            isBluetoothAvailable = bluetoothAdapter != null
        )
    )
    val uiState: StateFlow<MonitorUiState> = _uiState.asStateFlow()

    private var connectionJob: Job? = null

    init {
        refreshHistory()
    }

    fun refreshBondedDevices() {
        val devices = bluetoothManager.getBondedDevices()
        _uiState.update { current ->
            current.copy(
                bondedDevices = devices,
                selectedDeviceAddress = current.selectedDeviceAddress ?: devices.firstOrNull()?.address
            )
        }
    }

    fun selectDevice(address: String) {
        _uiState.update { it.copy(selectedDeviceAddress = address) }
    }

    fun connectToSelectedDevice() {
        val selectedAddress = _uiState.value.selectedDeviceAddress ?: run {
            _uiState.update {
                it.copy(
                    connectionStatus = ConnectionStatus.SELECT_DEVICE,
                    connectionDetail = null
                )
            }
            return
        }

        connectionJob?.cancel()
        connectionJob = viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isConnecting = true,
                    connectionStatus = ConnectionStatus.CONNECTING,
                    connectionDetail = selectedAddress,
                    radarTrail = emptyList()
                )
            }

            try {
                bluetoothManager.connect(selectedAddress) { rawLine ->
                    handleIncomingLine(rawLine)
                }
            } catch (_: CancellationException) {
                bluetoothManager.disconnect()
                throw CancellationException()
            } catch (t: Throwable) {
                bluetoothManager.disconnect()
                _uiState.update {
                    it.copy(
                        isConnecting = false,
                        connectionStatus = ConnectionStatus.FAILED,
                        connectionDetail = t.message
                    )
                }
            }
        }
    }

    fun disconnect() {
        connectionJob?.cancel()
        connectionJob = viewModelScope.launch {
            bluetoothManager.disconnect()
            _uiState.update {
                it.copy(
                    isConnecting = false,
                    connectionStatus = ConnectionStatus.DISCONNECTED,
                    connectionDetail = null
                )
            }
        }
    }

    private suspend fun handleIncomingLine(rawLine: String) {
        val parsed = SensorLineParser.parse(rawLine)
        if (parsed == null) {
            _uiState.update {
                it.copy(
                    lastMessage = rawLine,
                    isConnecting = false,
                    connectionStatus = ConnectionStatus.UNRECOGNIZED,
                    connectionDetail = null
                )
            }
            return
        }

        val reading = SensorReading(
            angleDeg = parsed.angleDeg,
            temperature = parsed.temperature,
            distanceCm = parsed.distanceCm,
            alarmEnabled = parsed.alarmEnabled,
            rawMessage = rawLine,
            recordedAt = System.currentTimeMillis()
        )

        repository.insertReading(reading)
        val history = repository.getRecentReadings()
        _uiState.update {
            it.copy(
                isConnecting = false,
                connectionStatus = ConnectionStatus.STREAMING,
                connectionDetail = null,
                latestReading = reading,
                recentReadings = history,
                radarTrail = (listOf(reading) + it.radarTrail).take(18),
                lastMessage = rawLine
            )
        }
    }

    private fun refreshHistory() {
        viewModelScope.launch(Dispatchers.IO) {
            val history = repository.getRecentReadings()
            _uiState.update {
                it.copy(
                    recentReadings = history,
                    latestReading = history.firstOrNull()
                )
            }
        }
    }

    override fun onCleared() {
        super.onCleared()
        connectionJob?.cancel()
        bluetoothManager.close()
    }

    companion object {
        fun factory(application: Application): ViewModelProvider.Factory =
            object : ViewModelProvider.Factory {
                @Suppress("UNCHECKED_CAST")
                override fun <T : androidx.lifecycle.ViewModel> create(modelClass: Class<T>): T {
                    return MainViewModel(application) as T
                }
            }
    }
}

private object SensorLineParser {
    private val keyedNumberPattern =
        Pattern.compile(
            """(?i)(angle|ang|a|temp|temperature|t|dist|distance|d|alarm|alert|sound|buzzer|b)\s*[:=]\s*(-?\d+(?:\.\d+)?)"""
        )
    private val genericNumberPattern = Pattern.compile("""-?\d+(?:\.\d+)?""")

    fun parse(line: String): ParsedReading? {
        val csvTokens = line.split(",").map { it.trim() }
        if (csvTokens.size >= 4) {
            val angle = csvTokens[0].toDoubleOrNull()
            val distance = csvTokens[1].toDoubleOrNull()
            val temperature = csvTokens[2].toDoubleOrNull()
            val alarm = csvTokens[3].toIntOrNull()
            if (angle != null && distance != null && temperature != null && alarm != null) {
                return ParsedReading(
                    angleDeg = angle,
                    distanceCm = distance,
                    temperature = temperature,
                    alarmEnabled = alarm == 1
                )
            }
        }

        val keyedMatches = keyedNumberPattern.matcher(line)
        var angle: Double? = null
        var temperature: Double? = null
        var distance: Double? = null
        var alarm: Boolean? = null

        while (keyedMatches.find()) {
            val key = keyedMatches.group(1)?.lowercase(Locale.ROOT).orEmpty()
            val value = keyedMatches.group(2)?.toDoubleOrNull() ?: continue
            when (key) {
                "angle", "ang", "a" -> angle = value
                "temp", "temperature", "t" -> temperature = value
                "dist", "distance", "d" -> distance = value
                "alarm", "alert", "sound", "buzzer", "b" -> alarm = value >= 1.0
            }
        }

        if (angle != null && temperature != null && distance != null && alarm != null) {
            return ParsedReading(
                angleDeg = angle,
                temperature = temperature,
                distanceCm = distance,
                alarmEnabled = alarm
            )
        }

        val genericMatches = genericNumberPattern.matcher(line)
        val values = mutableListOf<Double>()
        while (genericMatches.find()) {
            values += genericMatches.group().toDouble()
        }

        return if (values.size >= 4) {
            ParsedReading(
                angleDeg = values[0],
                distanceCm = values[1],
                temperature = values[2],
                alarmEnabled = values[3] >= 1.0
            )
        } else {
            null
        }
    }
}

private data class ParsedReading(
    val angleDeg: Double,
    val temperature: Double,
    val distanceCm: Double,
    val alarmEnabled: Boolean
)
