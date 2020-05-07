package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.os.Bundle;
import android.widget.TextView;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

import org.json.JSONObject;
public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        TextView tv = (TextView)findViewById((R.id.testTextView));

        try {
            URL url = new URL("https://parseapi.back4app.com/classes/Continentscountriescities_Country?limit=300&order=name&keys=name");
            HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
            urlConnection.setRequestProperty("X-Parse-Application-Id", "Ba2uRdWlbvbzwellUA7gTmmDEZf6bRPrXpnd8VaO"); // This is your app's application id
            urlConnection.setRequestProperty("X-Parse-REST-API-Key", "jxCa0OM9D0yd1S89ysT1Fjo2tkfJxorvFg3L36RC"); // This is your app's REST API key
            try {
                BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                StringBuilder stringBuilder = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    stringBuilder.append(line);
                }
                JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                //System.out.println(data.toString(2));
                tv.setText(data.toString(2));
            } finally {
                urlConnection.disconnect();
            }
        } catch (Exception e) {
            //System.out.println("Error: " + e.toString());
            tv.setText("Error");
        }
    }
}
