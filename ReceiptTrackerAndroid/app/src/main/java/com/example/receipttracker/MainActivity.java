package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.os.Bundle;
import android.util.Log;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.TextView;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Dictionary;
import java.util.Enumeration;
import java.util.Hashtable;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.json.JSONArray;
import org.json.JSONObject;

public class MainActivity extends AppCompatActivity {

    //Dictionary holding countries and countryIDs from the Back4App api
    Map<String,String> _dictCountries;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        AutoCompleteTextView _ddlCountries = (AutoCompleteTextView) findViewById(R.id._autoCompCountries);
        _ddlCountries.setThreshold(1);
        InitializeCDictionaries();

        //Find the countries and save it within the dictionary
        FindCountries();

        try {
            Thread.sleep(5000);
        }
        catch (InterruptedException e) {
            e.printStackTrace();
        }

        TextView tv = (TextView)findViewById((R.id.testTextView));
        Log.d("MainActivity: ", "I'm in main first");
        //testing drop down list filling
        List<String> myCountries = new ArrayList<String>(_dictCountries.keySet());

        ArrayAdapter<String> adapter = new ArrayAdapter<String>(this,
                android.R.layout.simple_dropdown_item_1line, myCountries);
        _ddlCountries.setAdapter(adapter);
    }

    private void InitializeCDictionaries()
    {
        _dictCountries = new Hashtable<>();
    }

    private void FindCountries()
    {
        //FindCountriesAsync findCountriesAsync = new FindCountriesAsync(this);
        //findCountriesAsync.execute();

        //testing Back4App api to get all countries
        (new Thread(new Runnable() {
            @Override
            public void run() {
                try {
                    URL url = new URL("https://parseapi.back4app.com/classes/Continentscountriescities_Country?count=1&limit=250&order=name&keys=name");
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
                        //Parse the data from the API using JSON
                        JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                        JSONArray results =  data.getJSONArray("results");
                        //Log.d("Objects in data", data.names().toString(2));

                        for(Integer i = 0; i < results.length(); ++i)
                        {
                            Log.d("Reading JSON Results", results.getJSONObject(i).get("name").toString());
                            _dictCountries.put(results.getJSONObject(i).get("name").toString(), results.getJSONObject(i).get("objectId").toString());
                        }

                        //sort the dictionary
                        _dictCountries = new TreeMap<>(_dictCountries);
                    }
                    finally {
                        urlConnection.disconnect();

                    }
                } catch (Exception e) {
                    Log.e("Something went wrong", e.toString());
                }
            }
        })).start();
    }
}
