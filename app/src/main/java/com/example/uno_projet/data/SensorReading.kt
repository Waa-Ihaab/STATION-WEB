package com.example.uno_projet.data

data class SensorReading(
    val id: Long = 0L,
    val angleDeg: Double,
    val temperature: Double,
    val distanceCm: Double,
    val alarmEnabled: Boolean,
    val rawMessage: String,
    val recordedAt: Long
)
