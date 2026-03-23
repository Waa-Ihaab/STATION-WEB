package com.example.uno_projet.bluetooth

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothSocket
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ensureActive
import kotlinx.coroutines.withContext
import java.io.IOException
import java.util.UUID
import kotlin.coroutines.coroutineContext

data class BluetoothDeviceInfo(
    val name: String,
    val address: String
)

class BluetoothSerialManager(
    private val adapter: BluetoothAdapter?
) {
    private var socket: BluetoothSocket? = null

    @SuppressLint("MissingPermission")
    fun getBondedDevices(): List<BluetoothDeviceInfo> {
        val currentAdapter = adapter ?: return emptyList()
        return currentAdapter.bondedDevices
            .map { device ->
                BluetoothDeviceInfo(
                    name = device.name ?: "Appareil inconnu",
                    address = device.address
                )
            }
            .sortedBy { it.name }
    }

    @SuppressLint("MissingPermission")
    suspend fun connect(
        address: String,
        onLineReceived: suspend (String) -> Unit
    ) = withContext(Dispatchers.IO) {
        val currentAdapter = adapter ?: error("Adaptateur Bluetooth indisponible")
        val device = currentAdapter.bondedDevices.firstOrNull { it.address == address }
            ?: error("Le module selectionne n'est pas associe")

        disconnect()
        currentAdapter.cancelDiscovery()

        val newSocket = device.createRfcommSocketToServiceRecord(SPP_UUID)
        socket = newSocket
        newSocket.connect()

        readLoop(newSocket, onLineReceived)
    }

    suspend fun disconnect() = withContext(Dispatchers.IO) {
        close()
    }

    fun close() {
        try {
            socket?.close()
        } catch (_: IOException) {
        } finally {
            socket = null
        }
    }

    private suspend fun readLoop(
        socket: BluetoothSocket,
        onLineReceived: suspend (String) -> Unit
    ) {
        val inputStream = socket.inputStream
        val buffer = ByteArray(256)
        val builder = StringBuilder()

        while (true) {
            coroutineContext.ensureActive()
            val count = inputStream.read(buffer)
            if (count == -1) break

            val chunk = String(buffer, 0, count)
            for (char in chunk) {
                if (char == '\n' || char == '\r') {
                    if (builder.isNotEmpty()) {
                        val line = builder.toString().trim()
                        builder.clear()
                        if (line.isNotEmpty()) {
                            onLineReceived(line)
                        }
                    }
                } else {
                    builder.append(char)
                }
            }
        }
    }

    companion object {
        private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    }
}
