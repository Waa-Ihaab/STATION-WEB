package com.example.uno_projet.data

import android.content.Context

class SensorRepository(context: Context) {
    private val dbHelper = SensorDatabaseHelper(context)

    fun insertReading(reading: SensorReading): Long = dbHelper.insertReading(reading)

    fun getRecentReadings(limit: Int = 20): List<SensorReading> = dbHelper.getRecentReadings(limit)
}
