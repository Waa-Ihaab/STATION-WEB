package com.example.uno_projet.data

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper

class SensorDatabaseHelper(context: Context) : SQLiteOpenHelper(
    context,
    DATABASE_NAME,
    null,
    DATABASE_VERSION
) {

    override fun onCreate(db: SQLiteDatabase) {
        db.execSQL(
            """
            CREATE TABLE $TABLE_READINGS (
                $COLUMN_ID INTEGER PRIMARY KEY AUTOINCREMENT,
                $COLUMN_ANGLE REAL NOT NULL,
                $COLUMN_TEMPERATURE REAL NOT NULL,
                $COLUMN_DISTANCE REAL NOT NULL,
                $COLUMN_ALARM INTEGER NOT NULL,
                $COLUMN_RAW_MESSAGE TEXT NOT NULL,
                $COLUMN_RECORDED_AT INTEGER NOT NULL
            )
            """.trimIndent()
        )
    }

    override fun onUpgrade(db: SQLiteDatabase, oldVersion: Int, newVersion: Int) {
        if (oldVersion < 2) {
            db.execSQL(
                "ALTER TABLE $TABLE_READINGS ADD COLUMN $COLUMN_ANGLE REAL NOT NULL DEFAULT 90.0"
            )
            db.execSQL(
                "ALTER TABLE $TABLE_READINGS ADD COLUMN $COLUMN_ALARM INTEGER NOT NULL DEFAULT 0"
            )
        }
    }

    fun insertReading(reading: SensorReading): Long {
        val values = ContentValues().apply {
            put(COLUMN_ANGLE, reading.angleDeg)
            put(COLUMN_TEMPERATURE, reading.temperature)
            put(COLUMN_DISTANCE, reading.distanceCm)
            put(COLUMN_ALARM, if (reading.alarmEnabled) 1 else 0)
            put(COLUMN_RAW_MESSAGE, reading.rawMessage)
            put(COLUMN_RECORDED_AT, reading.recordedAt)
        }
        return writableDatabase.insert(TABLE_READINGS, null, values)
    }

    fun getRecentReadings(limit: Int = 20): List<SensorReading> {
        val readings = mutableListOf<SensorReading>()
        val cursor = readableDatabase.query(
            TABLE_READINGS,
            null,
            null,
            null,
            null,
            null,
            "$COLUMN_RECORDED_AT DESC",
            limit.toString()
        )

        cursor.use {
            val idIndex = it.getColumnIndexOrThrow(COLUMN_ID)
            val angleIndex = it.getColumnIndexOrThrow(COLUMN_ANGLE)
            val temperatureIndex = it.getColumnIndexOrThrow(COLUMN_TEMPERATURE)
            val distanceIndex = it.getColumnIndexOrThrow(COLUMN_DISTANCE)
            val alarmIndex = it.getColumnIndexOrThrow(COLUMN_ALARM)
            val rawIndex = it.getColumnIndexOrThrow(COLUMN_RAW_MESSAGE)
            val recordedAtIndex = it.getColumnIndexOrThrow(COLUMN_RECORDED_AT)

            while (it.moveToNext()) {
                readings += SensorReading(
                    id = it.getLong(idIndex),
                    angleDeg = it.getDouble(angleIndex),
                    temperature = it.getDouble(temperatureIndex),
                    distanceCm = it.getDouble(distanceIndex),
                    alarmEnabled = it.getInt(alarmIndex) == 1,
                    rawMessage = it.getString(rawIndex),
                    recordedAt = it.getLong(recordedAtIndex)
                )
            }
        }

        return readings
    }

    companion object {
        private const val DATABASE_NAME = "sensor_monitor.db"
        private const val DATABASE_VERSION = 2

        private const val TABLE_READINGS = "sensor_readings"
        private const val COLUMN_ID = "_id"
        private const val COLUMN_ANGLE = "angle_deg"
        private const val COLUMN_TEMPERATURE = "temperature"
        private const val COLUMN_DISTANCE = "distance_cm"
        private const val COLUMN_ALARM = "alarm_state"
        private const val COLUMN_RAW_MESSAGE = "raw_message"
        private const val COLUMN_RECORDED_AT = "recorded_at"
    }
}
